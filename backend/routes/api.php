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
use App\Http\Controllers\AuditController;
use App\Models\Vehiculo;
use App\Models\Usuario;
use App\Models\AsignacionVehiculo;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    /*Route::get('/usuario', function (Request $request) {
        return $request->user()->load('permisos');
    });*/

    Route::get('/usuario', function (Request $request) {

    $usuario = $request->user()->load('permisos');

    $asignacionActiva = $usuario->asignaciones()
        ->where('activo', true)
        ->whereNull('fecha_fin')
        ->where('fecha_inicio', '<=', now())
        ->whereHas('vehiculo', function ($query) {
            $query->where('activo', true);
        })
        ->orderByDesc('id')
        ->with('vehiculo')
        ->first();

    $usuario->setAttribute('asignacion_activa', $asignacionActiva);

    return $usuario;
    });

    Route::get('/usuario/movil', function (Request $request) {
    $usuario = $request->user()->load('permisos');

    $asignaciones = $usuario->asignaciones()
        ->where('activo', true)
        ->whereNull('fecha_fin')
        ->where('fecha_inicio', '<=', now())
        ->whereHas('vehiculo', function ($query) {
            $query->where('activo', true);
        })
        ->with('vehiculo')
        ->get();

    $asignacionActiva = $asignaciones->count() === 1
        ? $asignaciones->first()
        : null;

    $usuario->setAttribute('asignacion_activa', $asignacionActiva);

    return $usuario;
});


    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', function () {
        return response()->json([
            'total_vehiculos' => Vehiculo::count(),
            'usuarios_activos' => Usuario::where('activo', true)->count(),
            'asignaciones_activas' => AsignacionVehiculo::where('activo', true)->count(),
            'vehiculos_activos' => Vehiculo::where('activo', true)->count(),
        ]);
    })->middleware('permiso:dashboard');

    Route::get('/dashboard/monitoreo', function () {
        return response()->json(['autorizado' => true]);
    })->middleware('permiso:ubicaciones');

    Route::middleware('permiso:usuarios')->group(function () {
        Route::post('/registro', [AuthController::class, 'registro']);
        Route::patch('/usuarios/{id}/password', [AuthController::class, 'editPassword']);
        Route::get('/usuarios', [UsuarioController::class, 'index']);
        Route::get('/usuarios/activos', [UsuarioController::class, 'usuariosActivos']);
        Route::patch('/usuarios/{id}', [UsuarioController::class, 'editar']);
        Route::patch('/usuarios/{id}/permisos', [UsuarioController::class, 'editarPermisos']);
        Route::get('/permisos', [PermisoController::class, 'listaPermisos']);
    });

    Route::middleware('permiso:tipos_vehiculo')->group(function () {
        Route::get('/tipos_vehiculos', [TipoVehiculoController::class, 'index']);
        Route::get('/tipos_vehiculos/activos', [TipoVehiculoController::class, 'tiposActivos']);
        Route::post('/tipos_vehiculos', [TipoVehiculoController::class, 'registro']);
        Route::put('/tipos_vehiculos/{id}', [TipoVehiculoController::class, 'editarInfo']);
    });

    Route::middleware('permiso:vehiculos')->group(function () {
        Route::get('/vehiculos', [VehiculoController::class, 'index']);
        Route::get('/vehiculos/activos', [VehiculoController::class, 'vehiculosActivos']);
        Route::post('/vehiculos', [VehiculoController::class, 'registro']);
        Route::put('/vehiculos/{id}', [VehiculoController::class, 'editInfo']);
    });

    Route::middleware('permiso:asignaciones')->group(function () {
        Route::get('/asignaciones', [AsignacionController::class, 'index']);
        Route::post('/asignaciones', [AsignacionController::class, 'registro']);
        Route::patch('/asignaciones/{id}', [AsignacionController::class, 'editarInfo']);
        Route::patch('/asignaciones/{id}/desasignar', [AsignacionController::class, 'desasignar']);
    });

    Route::middleware('permiso:enviar_ubicacion')->group(function () {
        Route::post('/ubicaciones', [UbicacionController::class, 'registrar']);
    });

    Route::middleware('permiso:ubicaciones')->group(function () {
        Route::get('/ubicaciones', [UbicacionController::class, 'ultimas']);
    });

    Route::middleware('permiso:historial_ubicaciones')->group(function () {
        Route::get('/ubicaciones/historial', [UbicacionController::class, 'historial']);
    });

    Route::middleware('permiso:auditoria')->group(function () {
        Route::get('/audits', [AuditController::class, 'index']);
        Route::get('/audits/{audit}', [AuditController::class, 'show']);
    });
});


//Ruta de prueba
Route::get('/prueba', function () {

    return response()->json([
        'success' => true,
        'message' => 'Conexión correcta con Laravel'
    ]);

});