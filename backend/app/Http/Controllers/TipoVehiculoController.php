<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TipoVehiculo;

class TipoVehiculoController extends Controller
{
    //
    public function index() {
        $tiposVehiculos = TipoVehiculo::all();

        return response()->json($tiposVehiculos);
    }

    public function registro (Request $request) {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'required|string',
            'activo' => 'required|boolean'
        ]);

        TipoVehiculo::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => $request->activo
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de vehículo creado correctamente'
        ]);
    }

    public function editarInfo($id, Request $request) {
        $tipo = TipoVehiculo::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'required|string',
            'activo' => 'required|boolean'
        ]);

        $tipo->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => $request->activo
        ]);
    
        return response()->json([
            'success' => true,
            'message'=> 'Cambios guardado correctamente'
        ]);
    }
}
