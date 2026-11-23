<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    private const MODULOS = [
        'App\Models\Usuario' => 'Usuario',
        'App\Models\AsignacionVehiculo' => 'Asignación de Vehículos',
        'App\Models\Permiso' => 'Permisos',
        'App\Models\Vehiculo' => 'Vehículo',
        'App\Models\TipoVehiculo' => 'Tipos de Vehículos',
    ];

    private function nombreModulo(?string $auditableType): ?string
    {
        if (!$auditableType) {
            return $auditableType;
        }

        return self::MODULOS[$auditableType] ?? class_basename($auditableType);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'event' => [
                'nullable',
                Rule::in(['created', 'updated', 'deleted', 'restored']),
            ],
            'user_id' => ['nullable', 'integer'],
            'registro' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $audits = Audit::with('user')
            ->when(
                $filters['event'] ?? null,
                fn ($query, $event) => $query->where('event', $event)
            )
            ->when(
                $filters['user_id'] ?? null,
                fn ($query, $userId) => $query->where('user_id', $userId)
            )
            ->when(
                $filters['from'] ?? null,
                fn ($query, $date) => $query->where(
                    'created_at',
                    '>=',
                    $date . ' 00:00:00'
                )
            )
            ->when(
                $filters['to'] ?? null,
                fn ($query, $date) => $query->where(
                    'created_at',
                    '<=',
                    $date . ' 23:59:59'
                )
            )
            ->latest()
            ->get();

        $audits->each(function ($audit) {
            $this->sanearPassword($audit);
            $audit->registro = $this->descripcionRegistro($audit);
            $audit->auditable_type = $this->nombreModulo($audit->auditable_type);
        });

        $busqueda = trim((string) ($filters['registro'] ?? ''));

        if ($busqueda !== '') {
            $audits = $audits->filter(function ($audit) use ($busqueda) {
                $texto = $audit->registro . ' ' . $audit->auditable_id;

                return stripos($texto, $busqueda) !== false;
            })->values();
        }

        return response()->json($audits);
    }

    public function show(Audit $audit)
    {
        $audit->load('user');
        $this->sanearPassword($audit);
        $audit->auditable_type = $this->nombreModulo($audit->auditable_type);

        return response()->json($audit);
    }

    private function sanearPassword($audit): void
    {
        foreach (['old_values', 'new_values'] as $campo) {
            $valores = $audit->{$campo};

            if (is_array($valores) && array_key_exists('password', $valores)) {
                $valores['password'] = '********';
                $audit->{$campo} = $valores;
            }
        }
    }

    private function descripcionRegistro(Audit $audit): string
    {
        if ($audit->event === 'sync') {
            return $this->descripcionSync($audit);
        }

        $datos = $audit->event === 'deleted'
            ? ($audit->old_values ?? [])
            : ($audit->new_values ?? []);

        $modelo = $audit->event === 'deleted'
            ? null
            : $audit->auditable()->first();

        return match ($audit->auditable_type) {
            'App\Models\Usuario' => trim(sprintf(
                '%s %s (CI: %s)',
                $datos['nombre'] ?? $modelo?->nombre ?? 'Sin nombre',
                $datos['apellido'] ?? $modelo?->apellido ?? '',
                $datos['ci'] ?? $modelo?->ci ?? '—'
            )),

            'App\Models\Vehiculo' => sprintf(
                '%s — %s %s',
                $datos['placa'] ?? $modelo?->placa ?? '—',
                $datos['marca'] ?? $modelo?->marca ?? '',
                $datos['modelo'] ?? $modelo?->modelo ?? ''
            ),

            'App\Models\TipoVehiculo' => $datos['nombre'] ?? $modelo?->nombre ?? '—',

            'App\Models\Permiso' => sprintf(
                '%s (%s)',
                $datos['nombre'] ?? $modelo?->nombre ?? '—',
                $datos['codigo'] ?? $modelo?->codigo ?? '—'
            ),

            'App\Models\AsignacionVehiculo' => $this->descripcionAsignacion($datos, $modelo),

            default => class_basename($audit->auditable_type) . ' #' . $audit->auditable_id,
        };
    }

    private function descripcionAsignacion(array $datos, $modelo): string
    {
        $usuarioId = $datos['usuario_id'] ?? $modelo?->usuario_id;
        $vehiculoId = $datos['vehiculo_id'] ?? $modelo?->vehiculo_id;

        $usuario = $usuarioId ? \App\Models\Usuario::find($usuarioId) : null;
        $vehiculo = $vehiculoId ? \App\Models\Vehiculo::find($vehiculoId) : null;

        return sprintf(
            '%s — %s',
            $usuario
                ? trim($usuario->nombre . ' ' . $usuario->apellido)
                : 'Usuario #' . $usuarioId,
            $vehiculo->placa ?? 'Vehículo #' . $vehiculoId
        );
    }

    private function descripcionSync(Audit $audit): string
    {
        $nuevos = $audit->new_values ?? [];
        $antiguos = $audit->old_values ?? [];

        $agregados = collect($nuevos['permisos'] ?? [])
            ->pluck('nombre')->filter()->implode(', ');
        $quitados = collect($antiguos['permisos'] ?? [])
            ->pluck('nombre')->filter()->implode(', ');

        if ($audit->auditable_type === 'App\Models\Usuario') {
            $usuario = $audit->auditable()->first();
            $persona = $usuario
                ? trim($usuario->nombre . ' ' . $usuario->apellido)
                : 'Usuario #' . $audit->auditable_id;

            $detalle = collect([
                $agregados ? "+{$agregados}" : null,
                $quitados ? "-{$quitados}" : null,
            ])->filter()->implode(' / ');

            return $detalle ? "{$persona} — Permisos: {$detalle}" : $persona;
        }

        return 'Sincronización #' . $audit->auditable_id;
    }
}