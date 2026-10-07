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

}
