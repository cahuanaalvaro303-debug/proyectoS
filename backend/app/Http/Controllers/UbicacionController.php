<?php

namespace App\Http\Controllers;

use App\Models\AsignacionVehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class UbicacionController extends Controller
{
    public function registrar(Request $request)
    {
        $datos = $request->validate([
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'velocidad' => 'required|numeric|min:0|max:1000',
            'direccion' => 'required|numeric|between:0,360',
            'precision' => 'required|numeric|min:0|max:100000',
            'registrado_en' => 'required|date',
        ]);

        $usuario = $request->user();

        if (!$usuario->activo) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario está inactivo.'
            ], 403);
        }

        $tienePermiso = $usuario->permisos()
            ->where('codigo', 'enviar_ubicacion')
            ->exists();

        if (!$tienePermiso) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para enviar ubicaciones.'
            ], 403);
        }

        $asignaciones = AsignacionVehiculo::where(
            'usuario_id',
            $usuario->id
        )
            ->where('activo', true)
            ->whereNull('fecha_fin')
            ->where('fecha_inicio', '<=', now())
            ->with('vehiculo')
            ->get();

        if ($asignaciones->count() !== 1) {
            return response()->json([
                'success' => false,
                'message' => $asignaciones->isEmpty()
                    ? 'No tienes un vehículo asignado actualmente.'
                    : 'Existe más de una asignación activa. Debe revisarse.'
            ], 422);
        }

        $asignacion = $asignaciones->first();
        $vehiculo = $asignacion->vehiculo;

        if (!$vehiculo || !$vehiculo->activo) {
            return response()->json([
                'success' => false,
                'message' => 'El vehículo asignado no está activo.'
            ], 422);
        }

        $registradoEn = Carbon::parse(
            $datos['registrado_en']
        )->utc()->format('Y-m-d H:i:s');

        $recibidoEn = now('UTC')->format('Y-m-d H:i:s');

        $resultado = DB::selectOne(
            'INSERT INTO ubicaciones (
                usuario_id,
                vehiculo_id,
                ubicacion,
                velocidad,
                direccion,
                precision,
                registrado_en,
                recibido_en
            )
            VALUES (
                ?, ?,
                ST_SetSRID(
                    ST_MakePoint(?, ?),
                    4326
                )::geography,
                ?, ?, ?, ?, ?
            )
            RETURNING id',
            [
                $usuario->id,
                $vehiculo->id,
                $datos['longitud'],
                $datos['latitud'],
                $datos['velocidad'],
                $datos['direccion'],
                $datos['precision'],
                $registradoEn,
                $recibidoEn,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Ubicación registrada correctamente.',
            'data' => [
                'id' => $resultado->id,
                'vehiculo_id' => $vehiculo->id,
                'registrado_en' => Carbon::parse(
                    $registradoEn,
                    'UTC'
                )->toISOString(),
                'recibido_en' => Carbon::parse(
                    $recibidoEn,
                    'UTC'
                )->toISOString(),
            ]
        ], 201);
    }

    
    public function ultimas(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario->activo) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario está inactivo.'
            ], 403);
        }

        $tienePermiso = $usuario->permisos()
            ->where('codigo', 'ubicaciones')
            ->exists();

        if (!$tienePermiso) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para consultar las ubicaciones.'
            ], 403);
        }

        $ubicaciones = DB::select("
            SELECT
                v.id AS vehiculo_id,
                v.placa,
                v.activo AS vehiculo_activo,
                u.latitud,
                u.longitud,
                u.velocidad,
                u.direccion,
                u.registrado_en,
                u.recibido_en,
                u.usuario_id,
                u.usuario_nombre,
                u.usuario_apellido
            FROM vehiculos v
            LEFT JOIN LATERAL (
                SELECT
                    ST_Y(ub.ubicacion::geometry) AS latitud,
                    ST_X(ub.ubicacion::geometry) AS longitud,
                    ub.velocidad,
                    ub.direccion,
                    ub.registrado_en,
                    ub.recibido_en,
                    ub.usuario_id,
                    us.nombre AS usuario_nombre,
                    us.apellido AS usuario_apellido
                FROM ubicaciones ub
                LEFT JOIN usuarios us ON us.id = ub.usuario_id
                WHERE ub.vehiculo_id = v.id
                ORDER BY ub.recibido_en DESC, ub.id DESC
                LIMIT 1
            ) u ON TRUE
            WHERE u.latitud IS NOT NULL 
            ORDER BY v.id
        ");

        return response()->json([
            'success' => true,
            'data' => $ubicaciones
        ]);
    }

    public function historial(Request $request)
   {
    $usuario = $request->user();

    if (!$usuario->activo) {
        return response()->json([
            'success' => false,
            'message' => 'El usuario está inactivo.'
        ], 403);
    }

    $tienePermiso = $usuario->permisos()
        ->where('codigo', 'historial_ubicaciones')
        ->exists();

    if (!$tienePermiso) {
        return response()->json([
            'success' => false,
            'message' => 'No tienes permiso para consultar el historial de ubicaciones.'
        ], 403);
    }

    $datos = $request->validate([
        'vehiculo_id' => 'required|integer|exists:vehiculos,id',
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        'per_page' => 'sometimes|integer|min:1|max:1000',
        'page' => 'sometimes|integer|min:1',
    ]);

    $fechaInicio = Carbon::parse(
        $datos['fecha_inicio']
    )->utc()->format('Y-m-d H:i:s');

    $fechaFin = Carbon::parse(
        $datos['fecha_fin']
    )->utc()->format('Y-m-d H:i:s');

    $porPagina = (int) ($datos['per_page'] ?? 500);
    $pagina = (int) ($datos['page'] ?? 1);

    $offset = ($pagina - 1) * $porPagina;

    $ubicaciones = DB::select(
            'SELECT
                ub.id,
                ub.vehiculo_id,
                v.placa,
                ub.usuario_id,
                us.nombre,
                us.apellido,
                ST_Y(ub.ubicacion::geometry) AS latitud,
                ST_X(ub.ubicacion::geometry) AS longitud,
                ub.velocidad,
                ub.direccion,
                to_char(
                    ub.registrado_en,
                    \'YYYY-MM-DD"T"HH24:MI:SS"Z"\'
                ) AS registrado_en,
                to_char(
                    ub.recibido_en,
                    \'YYYY-MM-DD"T"HH24:MI:SS"Z"\'
                ) AS recibido_en
            FROM ubicaciones ub
            INNER JOIN vehiculos v
                ON v.id = ub.vehiculo_id
            INNER JOIN usuarios us
                ON us.id = ub.usuario_id
            WHERE ub.vehiculo_id = ?
            AND ub.registrado_en >= ?
            AND ub.registrado_en <= ?
            ORDER BY ub.registrado_en ASC, ub.id ASC
            LIMIT ? OFFSET ?',
            [
                $datos['vehiculo_id'],
                $fechaInicio,
                $fechaFin,
                $porPagina + 1,
                $offset,
            ]
        );

        $hayMas = count($ubicaciones) > $porPagina;

        if ($hayMas) {
            array_pop($ubicaciones);
        }

        return response()->json([
            'success' => true,
            'data' => $ubicaciones,
            'pagination' => [
                'current_page' => $pagina,
                'per_page' => $porPagina,
                'has_more' => $hayMas,
            ],
        ]);
    }

}
