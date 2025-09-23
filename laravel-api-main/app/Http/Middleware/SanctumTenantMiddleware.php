<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class SanctumTenantMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Primero configurar el tenant
        $tenant = $this->configureTenant($request);

        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        // Ahora verificar el token en la base de datos correcta
        $token = $this->authenticateWithTenant($request);

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return $next($request);
    }

    /**
     * Configurar el tenant basado en el request
     */
    private function configureTenant(Request $request): ?string
    {
        $tenant = null;

        // Intentar obtener tenant del subdominio
        $host = $request->getHost();
        if ($host !== 'localhost' && $host !== '127.0.0.1') {
            if (preg_match('/^([a-zA-Z0-9-]+)\.(localhost|127\.0\.0\.1)/', $host, $matches)) {
                $tenant = $matches[1];
            }
        }

        // Si no hay tenant del subdominio, intentar del parámetro de ruta
        if (!$tenant) {
            $tenant = $request->route('tenant');
        }

        if ($tenant) {
            $this->setTenantDatabase($tenant);
            $request->attributes->set('tenant', $tenant);
            Log::info("Tenant configurado para autenticación: {$tenant}");
        }

        return $tenant;
    }

    /**
     * Configurar la base de datos para el tenant
     */
    private function setTenantDatabase(string $tenant): void
    {
        $databaseName = 'laravel_taller_' . $tenant;

        // Configurar la conexión del tenant
        Config::set('database.connections.tenant', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => $databaseName,
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                \PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ]);

        // Limpiar conexión anterior y establecer nueva
        DB::purge('tenant');
        Config::set('database.default', 'tenant');

        // Crear la base de datos si no existe
        $this->ensureTenantDatabaseExists($databaseName);

        Log::info("Base de datos configurada para auth: {$databaseName}");
    }

    /**
     * Crear la base de datos del tenant si no existe
     */
    private function ensureTenantDatabaseExists(string $databaseName): void
    {
        try {
            // Conectar sin especificar base de datos
            $defaultConnection = config('database.connections.mysql');
            $pdo = new \PDO(
                "mysql:host={$defaultConnection['host']};port={$defaultConnection['port']}",
                $defaultConnection['username'],
                $defaultConnection['password'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            // Crear base de datos
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            Log::info("Base de datos {$databaseName} verificada/creada para auth");

            // Ejecutar migraciones si es necesario
            $this->runTenantMigrationsIfNeeded($databaseName);
        } catch (\Exception $e) {
            Log::error("Error configurando BD del tenant para auth: " . $e->getMessage());
            throw new \Exception("No se pudo configurar la base de datos para el tenant: {$databaseName}");
        }
    }

    /**
     * Ejecutar migraciones para el tenant si es necesario
     */
    private function runTenantMigrationsIfNeeded(string $databaseName): void
    {
        try {
            // Verificar si la base de datos tiene las tablas necesarias
            $result = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ? AND table_name IN ('usuarios', 'tareas', 'personal_access_tokens')", [$databaseName]);

            if ($result[0]->count < 3) {
                // Faltan tablas, ejecutar migraciones
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--force' => true
                ]);
                Log::info("Migraciones ejecutadas para auth: {$databaseName}");
            }
        } catch (\Exception $e) {
            Log::error("Error en migraciones para auth {$databaseName}: " . $e->getMessage());
        }
    }

    /**
     * Autenticar usando el token en la base de datos del tenant
     */
    private function authenticateWithTenant(Request $request): ?PersonalAccessToken
    {
        $authorizationHeader = $request->header('Authorization');

        if (!$authorizationHeader || !str_starts_with($authorizationHeader, 'Bearer ')) {
            Log::info('No Bearer token found in request');
            return null;
        }

        $token = substr($authorizationHeader, 7);

        if (!$token) {
            Log::info('Empty token after Bearer');
            return null;
        }

        try {
            // Buscar el token en la base de datos del tenant
            $accessToken = PersonalAccessToken::findToken($token);

            if (!$accessToken) {
                Log::info('Token not found in tenant database');
                return null;
            }

            // Verificar si el token no ha expirado
            if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
                Log::info('Token expired');
                return null;
            }

            // Actualizar last_used_at
            $accessToken->forceFill(['last_used_at' => now()])->save();

            // Establecer el usuario autenticado
            Auth::login($accessToken->tokenable);

            Log::info('Token authenticated successfully for user: ' . $accessToken->tokenable->email);

            return $accessToken;
        } catch (\Exception $e) {
            Log::error('Error during token authentication: ' . $e->getMessage());
            return null;
        }
    }
}
