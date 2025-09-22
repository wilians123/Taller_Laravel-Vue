<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // Extraer el subdominio
        $subdomain = $this->extractSubdomain($host);

        if ($subdomain) {
            // Configurar la base de datos del tenant
            $this->setTenantDatabase($subdomain);

            // Guardar el tenant actual en el request
            $request->attributes->set('tenant', $subdomain);
            Log::info("Tenant configurado: {$subdomain} para host: {$host}");
        } else {
            Log::info("No se detectó tenant para host: {$host}");
        }

        return $next($request);
    }

    /**
     * Extraer el subdominio del host
     */
    private function extractSubdomain(string $host): ?string
    {
        // Para desarrollo con localhost
        if (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) {
            if (preg_match('/^([a-zA-Z0-9-]+)\.(localhost|127\.0\.0\.1)/', $host, $matches)) {
                return $matches[1];
            }
        }

        // Para producción
        $baseDomain = config('app.base_domain', 'localhost');
        if ($baseDomain !== 'localhost') {
            $pattern = '/^([a-zA-Z0-9-]+)\.' . preg_quote($baseDomain, '/') . '$/';
            if (preg_match($pattern, $host, $matches)) {
                return $matches[1];
            }
        }

        return null;
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
            } else {
                Log::info("Base de datos {$databaseName} ya tiene todas las tablas");
            }
        } catch (\Exception $e) {
            Log::error("Error en migraciones para {$databaseName}: " . $e->getMessage());
        }
    }
}
