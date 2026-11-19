<?php

namespace App\Http\Controllers;

use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\UserNotification;
use App\Support\Accounts\AccountOwnershipService;
use App\Support\Lavbe\LocalePackageMessages;
use App\Support\Push\WebPushConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AccountController extends Controller
{
    public function home(Request $request, string $locale, LocalePackageMessages $messages, WebPushConfiguration $webPush): Response|View
    {
        $user = $request->user();
        $confessions = Confession::query()->whereHas('ownership', fn ($query) => $query->where('user_id', $user->id))
            ->with(['revisions' => fn ($query) => $query->orderByDesc('revision')])->orderByDesc('updated_at')->limit(50)->get();
        $comments = ConfessionComment::query()->with(['confession', 'revisions' => fn ($query) => $query->orderByDesc('revision')])
            ->whereHas('ownership', fn ($query) => $query->where('user_id', $user->id))->orderByDesc('updated_at')->limit(50)->get();
        $follows = $user->followedConfessions()->public()->with('publicRevision')->orderByPivot('created_at', 'desc')->get();
        $notifications = $user->accountNotifications()->where('in_app', true)->orderByDesc('created_at')->limit(50)->get();

        $pwaMessages = $messages->group($locale, 'pwa');
        $notificationMessages = $messages->group($locale, 'notifications');
        $webPushPublicKey = $webPush->isReady() ? config('services.webpush.public_key') : null;

        return response()->view('account.home', compact('user', 'confessions', 'comments', 'follows', 'notifications', 'pwaMessages', 'notificationMessages', 'webPushPublicKey'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function preferences(Request $request, string $locale): RedirectResponse
    {
        $data = Validator::make($request->all(), [
            'in_app_notifications' => ['required', 'boolean'], 'email_notifications' => ['required', 'boolean'],
        ])->validate();
        $request->user()->forceFill([
            'in_app_notifications' => (bool) $data['in_app_notifications'],
            'email_notifications' => (bool) $data['email_notifications'],
        ])->save();

        return back()->with('status', __('account.preferences_saved'));
    }

    public function follow(Request $request, string $locale, string $publicId): RedirectResponse
    {
        $confession = Confession::query()->public()->where('locale', $locale)->where('public_id', $publicId)->firstOrFail();
        DB::table('confession_follows')->insertOrIgnore([
            'user_id' => $request->user()->id, 'confession_id' => $confession->id, 'created_at' => now(),
        ]);

        return back()->with('status', __('account.follow_saved'));
    }

    public function unfollow(Request $request, string $locale, string $publicId): RedirectResponse
    {
        $request->user()->followedConfessions()->where('public_id', $publicId)->detach();

        return back()->with('status', __('account.follow_removed'));
    }

    public function claimConfession(Request $request, string $locale, string $publicId, AccountOwnershipService $ownership): Response
    {
        $ownership->claimConfession($request, $request->user(), $publicId);

        return redirect()->route('account.home', ['locale' => $locale])->with('status', __('account.claim_complete'))
            ->withCookie(cookie()->forget('lavbe_owner_'.$publicId));
    }

    public function claimComment(Request $request, string $locale, string $publicId, AccountOwnershipService $ownership): Response
    {
        $ownership->claimComment($request, $request->user(), $publicId);

        return redirect()->route('account.home', ['locale' => $locale])->with('status', __('account.claim_complete'))
            ->withCookie(cookie()->forget('lavbe_comment_'.$publicId));
    }

    public function readNotification(Request $request, string $locale, int $notificationId): RedirectResponse
    {
        UserNotification::query()->where('user_id', $request->user()->id)->where('in_app', true)
            ->whereKey($notificationId)->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }
}
