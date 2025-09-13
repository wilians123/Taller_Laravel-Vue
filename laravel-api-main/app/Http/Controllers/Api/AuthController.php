<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Usuario;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Registro de un nuevo usuario
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:usuarios,email',
            'password' => 'required|string|min:6',
            'rol' => 'required|in:admin,usuario',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $usuario = Usuario::create($validated);

        // Generar token
        $token = $usuario->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'usuario' => $usuario,
            'token' => $token,
        ], 201);
    }

    /**
     * Login de usuario existente
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Debug: Verificar que el usuario existe
        $usuario = Usuario::where('email', $request->email)->first();

        if (!$usuario) {
            Log::info('Usuario no encontrado: ' . $request->email);
            throw ValidationException::withMessages([
                'email' => ['Usuario no encontrado.'],
            ]);
        }

        Log::info('Usuario encontrado: ' . $usuario->email);
        Log::info('Password hash en BD: ' . $usuario->password);
        Log::info('Password enviado: ' . $request->password);

        // Verificar password
        $passwordCheck = Hash::check($request->password, $usuario->password);
        Log::info('Password check result: ' . ($passwordCheck ? 'true' : 'false'));

        if (!$passwordCheck) {
            throw ValidationException::withMessages([
                'password' => ['Contraseña incorrecta.'],
            ]);
        }

        // Generar token
        $token = $usuario->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'usuario' => $usuario,
            'token' => $token,
        ]);
    }

    /**
     * Logout (revocar tokens)
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout exitoso, tokens revocados'
        ]);
    }
}
