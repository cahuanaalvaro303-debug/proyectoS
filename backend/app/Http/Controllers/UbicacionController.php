<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AsignacionVehiculo;
use App\Models\Ubicacion;

class UbicacionController extends Controller
{
    //
    public function registrar(Request $request){
        $datos = $request->validate([
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'velocidad' => 'required|numeric|min:0|max:1000',
            'direccion' => 'required|numeric|between:0,360',
            'precision' => 'required|numeric|min:0|max:100000',
            'registrado_en' => 'required|date'
        ]);

        $usuario = $request->user();
        $asignacion = AsignacionVehiculo::where('usuario_id', $usuario->id)
                ->where('activo', true)->whereNull('fecha_fin')
                ->with('vehiculo')->first();
        if(!$asignacion) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un vehiculo asignado actualmente'
            ], 422);
        }

        if(!$asignacion->vehiculo || !$asignacion->vehiculo->activo){
            return response()->json([
                'success' => false,
                'message' => 'El vehiculo asignado no esta activo'
            ], 422);
        }

        $idUbicacion = DB::table('ubicaciones')->insertGetId([
            'usuario_id' => $usuario->id,
            'vehiculo_id' => $asignacion->vehiculo->id,

            'ubicacion' => DB::raw(
                "ST_SetSRID(
                    ST_MakePoint(
                        {$datos['longitud']},
                        {$datos['latitud']} ), 4326)::geography"
            ),
            'velocidad' => $datos['velocidad'],
            'direccion' => $datos['direccion'],
            'precision' => $datos['precision'],
            'registrado_en' => $datos['registrado_en'],
            'recibido_en' => now()
        ], 'id');

        return response()->json([
            'success' => true,
            'message' => 'Ubicación registrada correctamente.',
            'data' => [
                'id' => $idUbicacion,
                'usuario_id' => $usuario->id,
                'vehiculo_id' => $asignacion->vehiculo_id,
                'registrado_en' => $datos['registrado_en'],
                'recibido_en' => now()->toISOString(),
            ]
        ], 201);
    }
}
