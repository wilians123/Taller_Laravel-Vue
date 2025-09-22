<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Ruta de prueba para la deteccion del tenant
Route::middleware('tenant')->get('/tenant-test', function (Request $request) {
    $tenant = $request->attributes->get('tenant');
    return response()->json([
        'host' => $request->getHost(),
        'tenant_detected' => $tenant,
        'message' => $tenant ? "Tenant '{$tenant}' detectado correctamente" : 'No se detectó tenant',
        'timestamp' => now()
    ]);
});



// Ruta de prueba para verificar conexión a BD del tenant
Route::middleware('tenant')->get('/tenant-db-test', function (Request $request) {
    $tenant = $request->attributes->get('tenant');

    try {
        // Probar conexión a la base de datos
        $databaseName = DB::connection()->getDatabaseName();

        // Contar usuarios en la BD actual
        $userCount = DB::table('usuarios')->count();

        return response()->json([
            'host' => $request->getHost(),
            'tenant_detected' => $tenant,
            'database_name' => $databaseName,
            'users_count' => $userCount,
            'message' => $tenant ? "Conectado a BD del tenant '{$tenant}'" : 'Conectado a BD por defecto',
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'host' => $request->getHost(),
            'tenant_detected' => $tenant,
            'error' => $e->getMessage(),
            'message' => 'Error conectando a la base de datos',
            'timestamp' => now()
        ], 500);
    }
});

// Rutas de autenticacion SIN tenant middleware
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Rutas protegidas SIN tenant middleware
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rutas para el controlador de usuarios
    Route::prefix('usuarios')->group(function () {
        Route::get('/listUsers', [UsuarioController::class, 'index']);
        Route::post('/addUser', [UsuarioController::class, 'store']);
        Route::get('/getUser/{id}', [UsuarioController::class, 'show']);
        Route::put('/updateUser/{id}', [UsuarioController::class, 'update']);
        Route::delete('/deleteUser/{id}', [UsuarioController::class, 'destroy']);
    });

    // Rutas para el controlador de tareas
    Route::prefix('tareas')->group(function () {
        Route::get('/list', [App\Http\Controllers\Api\TareaController::class, 'index']);
        Route::post('/create', [App\Http\Controllers\Api\TareaController::class, 'store']);
        Route::get('/show/{id}', [App\Http\Controllers\Api\TareaController::class, 'show']);
        Route::put('/update/{id}', [App\Http\Controllers\Api\TareaController::class, 'update']);
        Route::delete('/delete/{id}', [App\Http\Controllers\Api\TareaController::class, 'destroy']);
        Route::get('/pendientes', [App\Http\Controllers\Api\TareaController::class, 'tareasPendientes']);
    });
});
