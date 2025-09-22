<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes - Multitenancy por parámetro
|--------------------------------------------------------------------------
*/

// Rutas de prueba de tenant (mantener)
Route::middleware('tenant')->get('/tenant-test', function (Request $request) {
    $tenant = $request->attributes->get('tenant');
    return response()->json([
        'host' => $request->getHost(),
        'tenant_detected' => $tenant,
        'message' => $tenant ? "Tenant '{$tenant}' detectado correctamente" : 'No se detectó tenant',
        'timestamp' => now()
    ]);
});

Route::middleware('tenant')->get('/tenant-db-test', function (Request $request) {
    $tenant = $request->attributes->get('tenant');

    try {
        $databaseName = DB::connection()->getDatabaseName();
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

// Ruta para obtener usuario autenticado CON tenant
Route::middleware(['tenant', 'auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas de autenticación CON tenant (para subdominios)
Route::middleware('tenant')->post('/login', [AuthController::class, 'login']);
Route::middleware('tenant')->post('/register', [AuthController::class, 'register']);

// Rutas protegidas CON tenant + auth
Route::middleware(['tenant', 'auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rutas para usuarios
    Route::prefix('usuarios')->group(function () {
        Route::get('/listUsers', [UsuarioController::class, 'index']);
        Route::post('/addUser', [UsuarioController::class, 'store']);
        Route::get('/getUser/{id}', [UsuarioController::class, 'show']);
        Route::put('/updateUser/{id}', [UsuarioController::class, 'update']);
        Route::delete('/deleteUser/{id}', [UsuarioController::class, 'destroy']);
    });

    // Rutas para tareas
    Route::prefix('tareas')->group(function () {
        Route::get('/list', [App\Http\Controllers\Api\TareaController::class, 'index']);
        Route::post('/create', [App\Http\Controllers\Api\TareaController::class, 'store']);
        Route::get('/show/{id}', [App\Http\Controllers\Api\TareaController::class, 'show']);
        Route::put('/update/{id}', [App\Http\Controllers\Api\TareaController::class, 'update']);
        Route::delete('/delete/{id}', [App\Http\Controllers\Api\TareaController::class, 'destroy']);
        Route::get('/pendientes', [App\Http\Controllers\Api\TareaController::class, 'tareasPendientes']);
    });
});

// NUEVAS RUTAS CON TENANT POR PARÁMETRO
Route::prefix('tenant/{tenant}')->middleware(['tenant_param'])->group(function () {

    // Autenticación por tenant
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        // Usuarios por tenant
        Route::prefix('usuarios')->group(function () {
            Route::get('/listUsers', [UsuarioController::class, 'index']);
            Route::post('/addUser', [UsuarioController::class, 'store']);
            Route::get('/getUser/{id}', [UsuarioController::class, 'show']);
            Route::put('/updateUser/{id}', [UsuarioController::class, 'update']);
            Route::delete('/deleteUser/{id}', [UsuarioController::class, 'destroy']);
        });

        // Tareas por tenant
        Route::prefix('tareas')->group(function () {
            Route::get('/list', [App\Http\Controllers\Api\TareaController::class, 'index']);
            Route::post('/create', [App\Http\Controllers\Api\TareaController::class, 'store']);
            Route::get('/show/{id}', [App\Http\Controllers\Api\TareaController::class, 'show']);
            Route::put('/update/{id}', [App\Http\Controllers\Api\TareaController::class, 'update']);
            Route::delete('/delete/{id}', [App\Http\Controllers\Api\TareaController::class, 'destroy']);
            Route::get('/pendientes', [App\Http\Controllers\Api\TareaController::class, 'tareasPendientes']);
        });
    });
});
