<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PermisoController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/usuario', function (Request $request) {
        return $request->user()->load('permisos');
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    //Usuarios
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::patch('/usuarios/{id}', [UsuarioController::class, 'editar']);
    Route::patch('/usuarios/{id}/permisos', [UsuarioController::class, 'editarPermisos']);

    //Registro de nuevo usuario
    Route::post('/registro', [AuthController::class, 'registro']);

    //Permisos
    Route::get('/permisos', [PermisoController::class, 'listaPermisos']);
});

Route::post('/login', [AuthController::class, 'login']);
