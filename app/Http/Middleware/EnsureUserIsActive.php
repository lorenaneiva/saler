<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Ends the session of a user deactivated after signing in, whatever the session driver. */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->ativo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(401, 'Conta desativada.');
            }

            return to_route('login')->withErrors(['email' => 'Sua conta foi desativada. Procure a pessoa responsável pelo sistema.']);
        }

        return $next($request);
    }
}
