<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCollector
{
    /**
     * Les administrateurs peuvent également saisir des relevés : ils disposent
     * déjà d'une console dédiée mais gardent accès à l'espace collecte.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && ($user->is_collector || $user->is_admin), 403);

        return $next($request);
    }
}
