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
            ->paginate(20);

        $audits->through(function ($audit) {
            $audit->registro = $this->descripcionRegistro($audit);
            $audit->auditable_type = $this->nombreModulo($audit->auditable_type);

            return $audit;
        });

        return response()->json($audits);
    }

    public function show(Audit $audit)
    {
        $audit->load('user');
        $audit->auditable_type = $this->nombreModulo($audit->auditable_type);

        return response()->json($audit);
    }

    private function descripcionRegistro(Audit $audit): string
    {
        $datos = $audit->event === 'deleted'
            ? ($audit->old_values ?? [])
            : ($audit->new_values ?? []);

        return match ($audit->auditable_type) {
            'App\Models\Usuario' => trim(sprintf(
                '%s %s (CI: %s)',
                $datos['nombre'] ?? 'Sin nombre',
                $datos['apellido'] ?? '',
                $datos['ci'] ?? '—'
            )),

            'App\Models\Vehiculo' => sprintf(
                '%s — %s %s',
                $datos['placa'] ?? '—',
                $datos['marca'] ?? '',
                $datos['modelo'] ?? ''
            ),

            'App\Models\TipoVehiculo' => $datos['nombre'] ?? '—',

            'App\Models\Permiso' => sprintf(
                '%s (%s)',
                $datos['nombre'] ?? '—',
                $datos['codigo'] ?? '—'
            ),

            'App\Models\AsignacionVehiculo' => sprintf(
                'Usuario ID: %s | Vehículo ID: %s',
                $datos['usuario_id'] ?? '—',
                $datos['vehiculo_id'] ?? '—'
            ),

            default => class_basename($audit->auditable_type) . ' #' . $audit->auditable_id,
        };
    }
}