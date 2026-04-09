<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\RoleMiddleware as SpatieRoleMiddleware;

class RoleOrAdminMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): mixed
    {
        $user = $request->user();

        if ($user && (($user->user_type ?? null) === 'admin' || $user->hasRole('admin'))) {
            return $next($request);
        }

        return app(SpatieRoleMiddleware::class)->handle($request, $next, ...$roles);
    }
}
