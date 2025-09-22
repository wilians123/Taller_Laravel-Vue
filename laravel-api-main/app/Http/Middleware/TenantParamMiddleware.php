<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Artisan;

class TenantParamMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtener el tenant del parámetro de la ruta
        $tenant = $request->route('tenant');

        if ($tenant) {
            // Configurar la base de datos del tenant
            $this->setTenantDatabase($tenant);

            // Guardar el tenant en el request
            $request->attributes->set('tenant', $tenant);
            Log::info("Tenant configurado por parámetro: {$tenant}");
        }

        return $next($request);
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

        Log::info("Base de datos configurada: {$databaseName}");
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

            Log::info("Base de datos {$databaseName} verificada/creada");

            // Ejecutar migraciones si es necesario
            $this->runTenantMigrationsIfNeeded($databaseName);
        } catch (\Exception $e) {
            Log::error("Error configurando BD del tenant: " . $e->getMessage());
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

                Log::info("Migraciones ejecutadas para: {$databaseName}");
            }
        } catch (\Exception $e) {
            Log::error("Error en migraciones para {$databaseName}: " . $e->getMessage());
        }
    }
}
