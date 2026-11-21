@extends('layouts.app')

@section('title', __('guest.device_title').' · '.config('app.name'))

@section('content')
    <section class="write-page device-page" aria-labelledby="device-title">
        <header class="page-heading">
            <h1 id="device-title">{{ __('guest.device_title') }}</h1>
            <p>{{ __('guest.device_intro') }}</p>
        </header>

        @if(session('status'))<x-status tone="success">{{ session('status') }}</x-status>@endif

        @if($confessions->isEmpty())
            <div class="empty-state device-empty">
                <span class="empty-mark"><x-icon name="book-open" class="icon-large" /></span>
                <p>{{ __('guest.device_empty') }}</p>
                <a class="button" href="{{ route('confessions.create', ['locale' => $lavbeLocale['segment']]) }}"><x-icon name="pen-line" />{{ __('guest.write_title') }}</a>
            </div>
        @else
            <div class="device-confession-list">
                @foreach($confessions as $confession)
                    @php($revision = $confession->revisions->sortByDesc('revision')->first())
                    <article class="feed-card">
                        <p class="device-confession-status">{{ __('guest.device_status') }}: {{ __('state.'.($confession->status === 'unpublished' ? 'pending' : $confession->status)) }}</p>
                        <h2>
                            <a href="{{ route('confessions.status', ['locale' => app(App\Support\Lavbe\LocaleRegistry::class)->urlSegment($confession->locale), 'publicId' => $confession->public_id]) }}" @if($revision?->title) lang="{{ $confession->locale }}" @endif>{{ $revision?->title ?: __('lavbe.untitled_confession') }}</a>
                        </h2>
                    </article>
                @endforeach
            </div>
            @if($confessions->hasPages())<x-pagination :paginator="$confessions" />@endif
        @endif

        <div class="device-page-actions">
            @if($confessions->isNotEmpty())
                <a class="button" href="{{ route('confessions.create', ['locale' => $lavbeLocale['segment']]) }}"><x-icon name="pen-line" />{{ __('guest.write_title') }}</a>
            @endif
            @auth
                <a class="button button-secondary" href="{{ route('account.home', ['locale' => $lavbeLocale['segment']]) }}"><x-icon name="user-round" />{{ __('account.my_title', ['app_name' => config('app.name')]) }}</a>
            @else
                <a class="button button-secondary" href="{{ route('account.login', ['locale' => $lavbeLocale['segment']]) }}"><x-icon name="user-round" />{{ __('account.login_title') }}</a>
                <a class="button button-secondary" href="{{ route('account.register', ['locale' => $lavbeLocale['segment']]) }}">{{ __('account.register_title') }}</a>
            @endauth
        </div>

        @if($canForgetDevice)
            <details class="device-access">
                <summary>{{ __('guest.device_forget') }}</summary>
                <form method="post" action="{{ route('confessions.device.forget', ['locale' => $lavbeLocale['segment']]) }}">
                    @csrf
                    <p id="device-forget-hint">{{ __('guest.device_forget_hint') }}</p>
                    <button class="button button-secondary" type="submit" aria-describedby="device-forget-hint">{{ __('guest.device_forget') }}</button>
                </form>
            </details>
        @endif
    </section>
@endsection
