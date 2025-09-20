<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            // solo para guardar y loggearse
            $request->attributes->set('tenant', $subdomain);
            Log::info("Tenant detectado: {$subdomain} para host: {$host}");
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
            // Buscar patron tenant.localhost:8000
            if (preg_match('/^([a-zA-Z0-9-]+)\.(localhost|127\.0\.0\.1)/', $host, $matches)) {
                return $matches[1];
            }
        }

        // Para produccion, obtener dominio base desde config
        $baseDomain = config('app.base_domain', 'localhost');
        if ($baseDomain !== 'localhost') {
            $pattern = '/^([a-zA-Z0-9-]+)\.' . preg_quote($baseDomain, '/') . '$/';
            if (preg_match($pattern, $host, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
