<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AsignacionVehiculo;
use Illuminate\Validation\Rule;

class AsignacionController extends Controller
{
    //
    public function index() {
        $asignaciones = AsignacionVehiculo::with([
                'usuario',
                'vehiculo.tipoVehiculo'
        ])->get();

        return response()->json($asignaciones);
    }

    public function registro(Request $request) {
        $datos = $request->validate([
            'usuario_id' => [
                'required',
                'integer',
                Rule::exists('usuarios', 'id')->where('activo', true),
            ],
            'vehiculo_id' => [
                'required',
                'integer',
                Rule::exists('vehiculos', 'id')->where('activo', true),
            ],
            'fecha_inicio' => 'required|date|before_or_equal:now',
            'observaciones_entrega' => 'nullable|string'
        ]);

        $activa = fn ($campo, $id) => AsignacionVehiculo::where($campo, $id)
            ->where('activo', true)
            ->whereNull('fecha_fin')
            ->exists();

        if ($activa('usuario_id', $datos['usuario_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario ya tiene una asignación activa.'
            ], 409);
        }

        if ($activa('vehiculo_id', $datos['vehiculo_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'El vehículo ya está asignado.'
            ], 409);
        }

        AsignacionVehiculo::create([
            'usuario_id' => $datos['usuario_id'],
            'vehiculo_id' => $datos['vehiculo_id'],
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => null,
            'observaciones_entrega' => $datos['observaciones_entrega'] ?? null,
            'observaciones_devolucion' => null,
            'activo' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asignacion creada correctamente'
        ], 201);
    }

    public function editarInfo ($id, Request $request) {
        $asignacion = AsignacionVehiculo::findOrFail($id);

        $request->validate([
            'observaciones_entrega' => 'required|string',
        ]);

        $asignacion->update([
            'observaciones_entrega' => $request->observaciones_entrega
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cambios realizados correctamente'
        ]);
    }

    public function desasignar($id, Request $request){
        $asignacion = AsignacionVehiculo::findOrFail($id);

        if(!$asignacion->activo)
            return response()->json([
            'success' => false,
            'message' => 'No se puede desasignar'
            ], 400);

        $request->validate([
            'observaciones_devolucion' => 'nullable|string'
        ]);

        $asignacion->update([
            'observaciones_devolucion' => $request->observaciones_devolucion,
            'fecha_fin' => now(),
            'activo' => false
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Desasignación correcta'
        ]);
    }
}
