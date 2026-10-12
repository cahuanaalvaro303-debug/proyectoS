<?php

namespace App\Http\Controllers;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;


use Illuminate\Http\Request;

class AuthController extends Controller
{
    //
    public function login(Request $request) {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $key = 'login:'.$request->ip();

        if(RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Demasiados intentos. Intenta en '.
                RateLimiter::availableIn($key).'s'
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $usuario = Usuario::where('email', $request->email)->first();

        if ( !$usuario || !Hash::check($request->password, $usuario->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        $token = $usuario->createToken('token')->plainTextToken;

        return response()->json([
            'success' => true, 
            'message' => 'Login correcto',
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email 
            ],
            'token' => $token
        ], 200);
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();


        return response()->json([
            'success' => true,
            'message' => 'Logout correcto'
        ]);
    }

        public function registro(Request $request) {
        
        $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'ci' => 'required|string|max:20',
            'telefono' => 'required|string|max:30',
            'email' => 'required|email|unique:usuarios,email',
            'password' => 'required|string|min:8', 
            'activo' => 'required|boolean', 
            'permisos' => 'sometimes|array',
            'permisos.*' => 'integer|exists:permisos,id'
        ]);

        $usuario = Usuario::create([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'ci' => $request->ci,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'password' => $request->password,
            'activo' => $request->activo
        ]);

        $usuario->permisos()->sync($request->input('permisos', []));

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado correctamente'
        ], 201);
    }

}
