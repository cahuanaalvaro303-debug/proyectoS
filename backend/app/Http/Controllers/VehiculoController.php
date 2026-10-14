<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehiculo;
use Illuminate\Validation\Rule;

class VehiculoController extends Controller
{
    //
    public function index() {
        $vehiculos = Vehiculo::with('tipoVehiculo')->get();

        return response()->json($vehiculos);
    }

    public function registro(Request $request)
    {

        $request->validate([
            'placa' => 'required|string|max:20|unique:vehiculos,placa',
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:100',
            'anio' => 'required|integer',
            'color' => 'required|string|max:50',
            'tipo_vehiculo_id' => 'required|integer|exists:tipos_vehiculo,id',
            'activo' => 'required|boolean'
        ]);

        Vehiculo::create([
            'placa' => $request->placa,
            'marca' => $request->marca,
            'modelo' => $request->modelo,
            'anio' => $request->anio,
            'color' => $request->color,
            'tipo_vehiculo_id' => $request->tipo_vehiculo_id,
            'activo' => $request->activo
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehículo guardado correctamente'
        ]);
    }

    public function editInfo($id, Request $request) {
        $vehiculo = Vehiculo::findOrFail($id);

        $request->validate([
            'placa' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehiculos', 'placa')->ignore($vehiculo->id)],
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:100',
            'anio' => 'required|integer',
            'color' => 'required|string|max:100',
            'tipo_vehiculo_id' => 'required|integer|exists:tipos_vehiculo,id',
            'activo' => 'required|boolean'

        ]);

        $vehiculo->update([
            'placa' => $request->placa,
            'marca' => $request->marca,
            'modelo' => $request->modelo,
            'anio' => $request->anio,
            'color' => $request->color,
            'tipo_vehiculo_id' => $request->tipo_vehiculo_id,
            'activo' => $request->activo
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cambios realizados correctamente'
        ]);
    }
}
