<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour Railway + React Native.
 *
 * Problème : le proxy Railway (hikari) peut altérer les headers Content-Type,
 * ce qui empêche Laravel de parser le body JSON automatiquement.
 *
 * Solution : forcer l'Accept JSON + re-merger manuellement le JSON input
 * si le body n'a pas été parsé par Symfony.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Forcer Accept: application/json → les erreurs de validation retournent du JSON
        $request->headers->set('Accept', 'application/json');

        // 2. Si le body n'a pas été parsé (request()->all() est vide mais il y a du contenu)
        //    on tente de le parser manuellement depuis php://input
        if (
            empty($request->all())
            && $request->getContent()
            && in_array($request->method(), ['POST', 'PUT', 'PATCH'])
        ) {
            $body = json_decode($request->getContent(), true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($body)) {
                // Fusionne le JSON parsé dans la requête
                $request->merge($body);
            }
        }

        return $next($request);
    }
}
