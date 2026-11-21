<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user?->is_admin, 403);

        if (! $user->two_factor_confirmed_at) {
            return redirect()->route('security.edit');
        }

        return $next($request);
    }
}
