<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force Laravel à traiter toutes les requêtes /api/* comme du JSON.
 *
 * Nécessaire sur Railway (proxy hikari) qui peut altérer les headers Accept/Content-Type,
 * et pour les clients mobiles (Expo Go, React Native) qui n'envoient pas toujours Accept: application/json.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        // Force Laravel à parser le body comme JSON
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
