<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireFreshModeratorMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $verifiedAt = (int) $request->session()->get('moderator.mfa_verified_at', 0);
        $verifiedUser = (int) $request->session()->get('moderator.mfa_user_id', 0);
        $freshFor = (int) config('moderation.mfa_fresh_seconds');

        if (! $user
            || ! $user->moderatorMfa()->whereNotNull('confirmed_at')->exists()
            || $verifiedUser !== (int) $user->id
            || $verifiedAt <= 0
            || time() - $verifiedAt > $freshFor) {
            $request->session()->forget(['moderator.mfa_verified_at', 'moderator.mfa_user_id']);

            return redirect()->route('moderation.mfa.challenge', ['locale' => $request->route('locale')]);
        }

        return $next($request);
    }
}
