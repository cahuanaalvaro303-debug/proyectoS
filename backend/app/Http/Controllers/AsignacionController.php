<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AsignacionVehiculo;

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
        $request->validate([
            'usuario_id' => 'required|exists:usuarios,id',
             'vehiculo_id' => 'required|exists:vehiculos,id',
             'fecha_inicio' => 'required|date',
             'observaciones_entrega' => 'nullable|string'
        ]);

    AsignacionVehiculo::create([
        'usuario_id' => $request->usuario_id,
        'vehiculo_id' => $request->vehiculo_id,
        'fecha_inicio' => $request->fecha_inicio,
        'fecha_fin' => null,
        'observaciones_entrega' => $request->observaciones_entrega,
        'observaciones_devolucion' => null,
        'activo' => true
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Asignacion creada correctamente'
    ]);
    }

    public function editarInfo ($id, Request $request) {
        $asignacion = AsignacionVehiculo::findOrFail($id);

        $request->validate([
            'usuario_id' => 'required|exists:usuarios,id',
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'observaciones_entrega' => 'required|string',
        ]);

        $asignacion->update([
            'usuario_id' => $request->usuario_id,
            'vehiculo_id' => $request->vehiculo_id,
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
