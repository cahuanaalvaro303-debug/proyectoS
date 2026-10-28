<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\TipoVehiculoController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\UbicacionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/usuario', function (Request $request) {
        return $request->user()->load('permisos');
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    //Registro de nuevo usuario
    Route::post('/registro', [AuthController::class, 'registro']);

    //edit constraseña
    Route::patch('/usuarios/{id}/password', [AuthController::class, 'editPassword']);

    //Usuarios
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::get('/usuarios/activos', [UsuarioController::class, 'usuariosActivos']);
    Route::patch('/usuarios/{id}', [UsuarioController::class, 'editar']);
    Route::patch('/usuarios/{id}/permisos', [UsuarioController::class, 'editarPermisos']);

    //Permisos
    Route::get('/permisos', [PermisoController::class, 'listaPermisos']);

    //Tipos de Vehiculos
    Route::get('/tipos_vehiculos', [TipoVehiculoController::class, 'index']);
    Route::get('/tipos_vehiculos/activos', [TipoVehiculoController::class, 'tiposActivos']);
    Route::post('/tipos_vehiculos', [TipoVehiculoController::class, 'registro']);
    Route::put('/tipos_vehiculos/{id}', [TipoVehiculoController::class, 'editarInfo']);

    //Vehiculos
    Route::get('/vehiculos', [VehiculoController::class, 'index']);
    Route::get('/vehiculos/activos', [VehiculoController::class, 'vehiculosActivos']);
    Route::post('/vehiculos', [VehiculoController::class, 'registro']);
    Route::put('/vehiculos/{id}', [VehiculoController::class, 'editInfo']);

    //Asignaciones
    Route::get('/asignaciones', [AsignacionController::class, 'index']);
    Route::post('/asignaciones', [AsignacionController::class, 'registro']);
    Route::patch('/asignaciones/{id}', [AsignacionController::class, 'editarInfo']);
    Route::patch('/asignaciones/{id}/desasignar', [AsignacionController::class, 'desasignar']);

    //Ubicaciones
    Route::post('/ubicaciones', [UbicacionController::class, 'registrar'] );
    Route::get('/ubicaciones', [UbicacionController::class, 'ultimas']);
    Route::get('/ubicaciones/historial', [UbicacionController::class, 'historial']);
    
});

Route::post('/login', [AuthController::class, 'login']);
