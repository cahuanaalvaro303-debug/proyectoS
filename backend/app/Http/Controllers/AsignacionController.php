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
             'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
             'observaciones_entrega' => 'nullable|string',
             'observaciones_devolucion' => 'nullable|string',
             'activo' => 'required|boolean'
        ]);

    AsignacionVehiculo::create([
        'usuario_id' => $request->usuario_id,
        'vehiculo_id' => $request->vehiculo_id,
        'fecha_inicio' => $request->fecha_inicio,
        'fecha_fin' => $request->fecha_fin,
        'observaciones_entrega' => $request->observaciones_entrega,
        'observaciones_devolucion' => $request->observaciones_devolucion,
        'activo' => $request->activo
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Asignacion creada correctamente'
    ]);
    }
}
