<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Keeps a user whose password was chosen by someone else (initial or reset) on the password page until it changes. */
class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password && ! $request->routeIs('password.edit', 'password.update', 'logout')) {
            if ($request->expectsJson()) {
                abort(403, 'Altere sua senha antes de continuar.');
            }

            return to_route('password.edit');
        }

        return $next($request);
    }
}
