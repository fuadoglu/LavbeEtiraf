<?php

namespace App\Http\Controllers;

use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\UserNotification;
use App\Support\Accounts\AccountOwnershipService;
use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AccountController extends Controller
{
    public function home(Request $request, string $locale): Response|View
    {
        $user = $request->user();
        $confessions = Confession::query()->whereHas('ownership', fn ($query) => $query->where('user_id', $user->id))
            ->with(['revisions' => fn ($query) => $query->orderByDesc('revision')->limit(1)])->orderByDesc('updated_at')->orderByDesc('id')
            ->paginate((int) config('lavbe.pagination.account'), ['*'], 'confessions_page')->withQueryString();
        $comments = ConfessionComment::query()->with(['confession', 'revisions' => fn ($query) => $query->orderByDesc('revision')->limit(1)])
            ->whereHas('ownership', fn ($query) => $query->where('user_id', $user->id))->orderByDesc('updated_at')->orderByDesc('id')
            ->paginate((int) config('lavbe.pagination.account'), ['*'], 'comments_page')->withQueryString();
        $follows = $user->followedConfessions()->public()->with('publicRevision')->orderByPivot('created_at', 'desc')->orderByDesc('confessions.id')
            ->paginate((int) config('lavbe.pagination.account'), ['*'], 'follows_page')->withQueryString();
        $notifications = $user->accountNotifications()->where('in_app', true)->orderByDesc('created_at')->orderByDesc('id')
            ->paginate((int) config('lavbe.pagination.account'), ['*'], 'notifications_page')->withQueryString();
        foreach ([$confessions, $comments, $follows, $notifications] as $list) {
            abort_if($list->currentPage() > $list->lastPage(), 404);
        }

        return response()->view('account.home', compact('user', 'confessions', 'comments', 'follows', 'notifications'))
            ->header('Cache-Control', \App\Support\Http\ResponseHeaders::CACHE_PRIVATE_NO_STORE)
            ->header('X-Robots-Tag', \App\Support\Http\ResponseHeaders::ROBOTS_PRIVATE)
            ->header('Referrer-Policy', \App\Support\Http\ResponseHeaders::REFERRER_NO_REFERRER);
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
        $confession = Confession::query()->public()->where('locale', app(LocaleRegistry::class)->contentCode($locale))->where('public_id', $publicId)->firstOrFail();
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
