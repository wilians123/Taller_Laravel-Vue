<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes nueva rama
|--------------------------------------------------------------------------
*/

// Ruta para obtener usuario autenticado
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas de autenticación (sin middleware)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Rutas protegidas con auth:sanctum
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
