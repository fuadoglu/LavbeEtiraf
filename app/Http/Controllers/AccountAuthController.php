<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AccountAuthController extends Controller
{
    public function registerForm(string $locale): View
    {
        return view('account.register');
    }

    public function register(Request $request, string $locale): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:'.config('lavbe.limits.account_name')],
            'email' => ['required', 'email:rfc', 'max:'.config('lavbe.limits.account_email'), 'unique:users,email'],
            'password' => ['required', 'string', 'min:'.config('lavbe.limits.account_password_min'), 'max:'.config('lavbe.limits.account_password'), 'confirmed'],
        ])->validate();
        $user = User::query()->create([
            'name' => trim(strip_tags($data['name'])), 'email' => mb_strtolower(trim($data['email'])),
            'password' => $data['password'], 'preferred_locale' => $locale,
        ]);
        $previousSessionId = $request->session()->getId();
        Auth::login($user);
        $request->session()->regenerate();
        $this->preserveGuestOwnership($previousSessionId, $request->session()->getId());
        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice', ['locale' => $locale]);
    }

    public function loginForm(string $locale): View
    {
        return view('account.login');
    }

    public function login(Request $request, string $locale): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $credentials = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc', 'max:'.config('lavbe.limits.account_email')], 'password' => ['required', 'string', 'max:'.config('lavbe.limits.account_password')],
        ])->validate();
        $credentials['email'] = mb_strtolower(trim($credentials['email']));
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => __('account.login_failed')])->onlyInput('email');
        }
        $previousSessionId = $request->session()->getId();
        $request->session()->regenerate();
        $this->preserveGuestOwnership($previousSessionId, $request->session()->getId());
        $request->user()->forceFill(['preferred_locale' => $locale])->save();

        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('account.home', ['locale' => $locale])
            : redirect()->route('verification.notice', ['locale' => $locale]);
    }

    public function logout(Request $request, string $locale): RedirectResponse
    {
        $request->user()?->pushSubscriptions()->update(['active' => false, 'updated_at' => now()]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home', ['locale' => $locale]);
    }

    public function verificationNotice(string $locale): Response|View
    {
        if (request()->user()->hasVerifiedEmail()) {
            return redirect()->route('account.home', ['locale' => $locale]);
        }

        return response()->view('account.verify-notice')
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function resendVerification(Request $request, string $locale): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('status', __('account.verification_resent'));
    }

    public function verify(Request $request, string $locale, int $id, string $hash): RedirectResponse
    {
        $user = $request->user();
        abort_unless((int) $user->getKey() === $id && hash_equals(sha1($user->getEmailForVerification()), $hash), 403);
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('account.home', ['locale' => $locale])->with('status', __('account.verification_complete'));
    }

    public function forgotForm(string $locale): View
    {
        return view('account.forgot-password');
    }

    public function forgot(Request $request, string $locale): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = Validator::make($request->all(), ['email' => ['required', 'email:rfc', 'max:'.config('lavbe.limits.account_email')]])->validate();
        Password::sendResetLink(['email' => mb_strtolower(trim($data['email']))]);

        return back()->with('status', __('account.reset_link_sent'));
    }

    public function resetForm(Request $request, string $locale, string $token): View
    {
        return view('account.reset-password', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function reset(Request $request, string $locale): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = Validator::make($request->all(), [
            'token' => ['required', 'string'], 'email' => ['required', 'email:rfc', 'max:'.config('lavbe.limits.account_email')],
            'password' => ['required', 'string', 'min:'.config('lavbe.limits.account_password_min'), 'max:'.config('lavbe.limits.account_password'), 'confirmed'],
        ])->validate();
        $status = Password::reset([
            'email' => mb_strtolower(trim($data['email'])), 'password' => $data['password'],
            'password_confirmation' => $request->input('password_confirmation'), 'token' => $data['token'],
        ], function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('account.login', ['locale' => $locale])->with('status', __('account.password_reset'))
            : back()->withErrors(['email' => __('account.reset_failed')])->onlyInput('email');
    }

    private function preserveGuestOwnership(string $oldSessionId, string $newSessionId): void
    {
        if (hash_equals($oldSessionId, $newSessionId)) {
            return;
        }
        $oldHash = hash_hmac('sha256', $oldSessionId, (string) config('app.key'));
        $newHash = hash_hmac('sha256', $newSessionId, (string) config('app.key'));
        DB::transaction(function () use ($oldHash, $newHash): void {
            DB::table('confession_ownerships')->whereNull('user_id')->where('session_token_hash', $oldHash)->update(['session_token_hash' => $newHash]);
            DB::table('comment_ownerships')->whereNull('user_id')->where('session_token_hash', $oldHash)->update(['session_token_hash' => $newHash]);
        }, 3);
    }
}
