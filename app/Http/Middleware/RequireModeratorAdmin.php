<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireModeratorAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && $user->moderatorGrants()
            ->where('role', 'admin')
            ->where('locale', '*')
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->exists(), 404);

        return $next($request);
    }
}
