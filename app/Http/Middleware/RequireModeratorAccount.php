<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireModeratorAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('moderation.login', ['locale' => $request->route('locale')]);
        }

        abort_unless($user->email_verified_at !== null, 404);
        abort_unless($user->moderatorGrants()->where('is_active', true)->whereNull('revoked_at')->exists(), 404);

        return $next($request);
    }
}
