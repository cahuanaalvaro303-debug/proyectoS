<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Http\Resources\UsuarioResource;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    //
    public function index()
    {
        $usuarios = Usuario::all();
        $usuarios->load('permisos');
        return UsuarioResource::collection($usuarios);
    }

    public function editar($id, Request $request)
    {
        $usuario = Usuario::findOrFail($id);
        $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'ci' => [
                'required',
                'string',
                'max:20',
                Rule::unique('usuarios','ci')->ignore($usuario->id)],
            'telefono' => 'required|string|max:30',
            'email' => [
                'required',
                'email',
                Rule::unique('usuarios', 'email')->ignore($usuario->id)
            ],
            'activo' => 'required|boolean',
        ]);

        $usuario->update([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'ci' => $request->ci,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'activo' => $request->activo
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cambios realizado correctamente'
        ], 200);
    }

    public function editarPermisos($id, Request $request)
    {
        $usuario = Usuario::findOrFail($id);
        $request->validate([
            'permisos' => 'sometimes|array',
            'permisos.*' => 'integer|exists:permisos,id'
        ]);
        $usuario->permisos()->sync($request->input('permisos', []));

        return response()->json([
            'success' => true,
            'message' => 'Cambios realizado correctamente'
        ]);
    }
}
