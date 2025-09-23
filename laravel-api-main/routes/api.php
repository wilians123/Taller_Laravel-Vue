<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TareaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas de prueba para el tenant
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

// RUTAS PRINCIPALES CON TENANT POR PARÁMETRO
Route::prefix('tenant/{tenant}')->group(function () {
    // Autenticación por tenant (NO requieren autenticación)
    Route::post('/login', [AuthController::class, 'login'])->middleware('tenant_param');
    Route::post('/register', [AuthController::class, 'register'])->middleware('tenant_param');

    // Rutas protegidas - usar el nuevo middleware
    Route::middleware('sanctum_tenant')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        // Ruta para obtener usuario autenticado
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

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
            Route::get('/list', [TareaController::class, 'index']);
            Route::post('/create', [TareaController::class, 'store']);
            Route::get('/show/{id}', [TareaController::class, 'show']);
            Route::put('/update/{id}', [TareaController::class, 'update']);
            Route::delete('/delete/{id}', [TareaController::class, 'destroy']);
            Route::get('/pendientes', [TareaController::class, 'tareasPendientes']);
        });
    });
});

// RUTAS PARA DOMINIO PRINCIPAL (localhost sin subdominio)
// Estas usan la base de datos principal 'laravel_taller'
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Ruta para obtener usuario autenticado SIN tenant (BD principal)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas protegidas para dominio principal - usando auth:sanctum normal
Route::middleware('auth:sanctum')->group(function () {
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
        Route::get('/list', [TareaController::class, 'index']);
        Route::post('/create', [TareaController::class, 'store']);
        Route::get('/show/{id}', [TareaController::class, 'show']);
        Route::put('/update/{id}', [TareaController::class, 'update']);
        Route::delete('/delete/{id}', [TareaController::class, 'destroy']);
        Route::get('/pendientes', [TareaController::class, 'tareasPendientes']);
    });
});
