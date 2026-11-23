<?php

namespace App\Http\Controllers;

use App\Models\ModeratorMfa;
use App\Support\Http\ResponseHeaders;
use App\Support\Moderation\LocalMfaBypass;
use App\Support\Moderation\ModeratorMfaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ModeratorAuthController extends Controller
{
    public function loginForm(string $locale): Response
    {
        return $this->privateView('moderation.login');
    }

    public function login(Request $request, string $locale): Response
    {
        $data = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:'.config('lavbe.limits.moderator_email')],
            'password' => ['required', 'string', 'max:'.config('lavbe.limits.moderator_password')],
        ])->validate();
        $rateKey = 'moderator-login:'.hash_hmac('sha256', mb_strtolower($data['email']).'|'.$request->ip(), (string) config('app.key'));
        $limit = config('lavbe.rate_limits.actions.moderator_login');
        if (RateLimiter::tooManyAttempts($rateKey, $limit['attempts'])) {
            return back()->withErrors(['email' => __('moderation.too_many')]);
        }
        RateLimiter::hit($rateKey, $limit['decay_seconds']);

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']], false)) {
            return back()->withErrors(['email' => __('moderation.login_failed')])->onlyInput('email');
        }
        $request->session()->regenerate();
        $user = $request->user();
        if (! $user || ! $user->email_verified_at || ! $user->moderatorGrants()->where('is_active', true)->whereNull('revoked_at')->exists()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => __('moderation.login_failed')])->onlyInput('email');
        }
        RateLimiter::clear($rateKey);
        $request->session()->forget(['moderator.mfa_verified_at', 'moderator.mfa_user_id']);

        if (LocalMfaBypass::enabled()) {
            return redirect()->route('moderation.queue', ['locale' => $locale]);
        }

        if ($user->moderatorMfa()->whereNotNull('confirmed_at')->exists()) {
            return redirect()->route('moderation.mfa.challenge', ['locale' => $locale]);
        }

        return redirect()->route('moderation.mfa.enroll', ['locale' => $locale]);
    }

    public function enroll(Request $request, string $locale, ModeratorMfaService $mfa): Response
    {
        $this->assertActiveAccount($request);
        if (LocalMfaBypass::enabled()) {
            return redirect()->route('moderation.queue', ['locale' => $locale]);
        }
        $record = ModeratorMfa::query()->firstOrCreate(['user_id' => $request->user()->id]);
        if ($record->confirmed_at) {
            return redirect()->route('moderation.mfa.challenge', ['locale' => $locale]);
        }
        if (! $record->pending_secret_ciphertext) {
            $record->pending_secret_ciphertext = $mfa->encryptSecret($secret = $mfa->newSecret());
            $record->save();
        } else {
            $secret = $mfa->decryptSecret($record->pending_secret_ciphertext);
        }

        return $this->privateView('moderation.enroll', [
            'secret' => $secret,
            'provisioningUri' => $mfa->provisioningUri($secret, (string) $request->user()->email),
        ]);
    }

    public function confirmEnrollment(Request $request, string $locale, ModeratorMfaService $mfa): Response
    {
        $this->assertActiveAccount($request);
        $data = Validator::make($request->all(), ['code' => ['required', 'string', 'regex:/^\d{6}$/']])->validate();
        $codes = $mfa->confirmEnrollment($request->user(), $data['code']);
        if ($codes === []) {
            return back()->withErrors(['code' => __('moderation.invalid_code')]);
        }
        $request->session()->regenerate();
        $this->markMfaFresh($request);

        return $this->privateView('moderation.recovery-codes', ['codes' => $codes]);
    }

    public function challenge(Request $request, string $locale): Response
    {
        $this->assertActiveAccount($request);
        if (LocalMfaBypass::enabled()) {
            return redirect()->route('moderation.queue', ['locale' => $locale]);
        }
        if (! $request->user()->moderatorMfa()->whereNotNull('confirmed_at')->exists()) {
            return redirect()->route('moderation.mfa.enroll', ['locale' => $locale]);
        }

        return $this->privateView('moderation.challenge');
    }

    public function verifyChallenge(Request $request, string $locale, ModeratorMfaService $mfa): Response
    {
        $this->assertActiveAccount($request);
        $data = Validator::make($request->all(), ['code' => ['required', 'string', 'min:'.config('lavbe.limits.moderator_code_min'), 'max:'.config('lavbe.limits.moderator_code'), 'regex:/^[A-Za-z0-9_-]+$/']])->validate();
        $rateKey = 'moderator-mfa:'.hash_hmac('sha256', $request->user()->id.'|'.$request->ip(), (string) config('app.key'));
        $limit = config('lavbe.rate_limits.actions.moderator_mfa');
        if (RateLimiter::tooManyAttempts($rateKey, $limit['attempts'])) {
            return back()->withErrors(['code' => __('moderation.too_many')]);
        }
        RateLimiter::hit($rateKey, $limit['decay_seconds']);

        if (! $mfa->verify($request->user(), $data['code'])) {
            return back()->withErrors(['code' => __('moderation.invalid_code')]);
        }
        RateLimiter::clear($rateKey);
        $request->session()->regenerate();
        $this->markMfaFresh($request);

        return redirect()->route('moderation.queue', ['locale' => $locale]);
    }

    public function logout(Request $request, string $locale): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('moderation.login', ['locale' => $locale]);
    }

    private function assertActiveAccount(Request $request): void
    {
        abort_unless($request->user() && $request->user()->email_verified_at, 404);
        abort_unless($request->user()->moderatorGrants()->where('is_active', true)->whereNull('revoked_at')->exists(), 404);
    }

    private function markMfaFresh(Request $request): void
    {
        $request->session()->put([
            'moderator.mfa_verified_at' => time(),
            'moderator.mfa_user_id' => (int) $request->user()->id,
        ]);
    }

    private function privateView(string $view, array $data = [], int $status = 200): Response
    {
        return response()->view($view, $data, $status)
            ->header('Cache-Control', ResponseHeaders::CACHE_PRIVATE_NO_STORE)
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', ResponseHeaders::ROBOTS_PRIVATE)
            ->header('Referrer-Policy', ResponseHeaders::REFERRER_NO_REFERRER);
    }
}
