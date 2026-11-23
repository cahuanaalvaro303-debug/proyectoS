<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string $codigo)
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.'
            ], 401);
        }

        if (!$usuario->activo) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario está inactivo.'
            ], 403);
        }

        $tienePermiso = $usuario->permisos()
            ->where('codigo', $codigo)
            ->exists();

        if (!$tienePermiso) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta acción.'
            ], 403);
        }

        return $next($request);
    }
}