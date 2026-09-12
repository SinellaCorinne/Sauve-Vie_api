<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour Railway + React Native.
 *
 * Problème diagnostiqué : Railway (proxy hikari) supprime le Content-Type header
 * et le body des requêtes POST — raw_content et php_input arrivent vides.
 *
 * Ce middleware tente de récupérer les données depuis toutes les sources possibles.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Forcer Accept JSON → les erreurs retournent du JSON
        $request->headers->set('Accept', 'application/json');

        // 2. Restaurer Content-Type si effacé par le proxy
        if (! $request->headers->get('Content-Type')) {
            $request->headers->set('Content-Type', 'application/json');
        }

        // 3. Si le body est vide malgré une requête POST/PUT/PATCH
        //    → tenter de lire depuis STDIN (php://input) directement
        if (
            empty($request->all())
            && in_array($request->method(), ['POST', 'PUT', 'PATCH'])
        ) {
            // Tentative 1 : getContent() de Symfony
            $raw = $request->getContent();

            // Tentative 2 : php://input direct (bypass Symfony cache)
            if (empty($raw)) {
                $raw = file_get_contents('php://input');
            }

            if (! empty($raw)) {
                $body = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($body)) {
                    $request->merge($body);
                }
            }
        }

        return $next($request);
    }
}
