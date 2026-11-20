@extends('layouts.app')

@section('title', __('guest.device_title').' · Lavbe')

@section('content')
    <section class="write-page submission-result">
        <h1>{{ __('guest.device_title') }}</h1>
        <p>{{ __('guest.device_intro') }}</p>
        @if(session('status'))<x-status tone="success">{{ session('status') }}</x-status>@endif
        @if($confessions->isEmpty())
            <p>{{ __('guest.device_empty') }}</p>
            <a class="button button-primary" href="{{ route('confessions.create', ['locale' => $lavbeLocale['segment']]) }}">{{ __('guest.write_title') }}</a>
            <a href="{{ route('account.login', ['locale' => $lavbeLocale['segment']]) }}">{{ __('account.login_title') }}</a>
        @else
            <div class="device-confession-list">
                @foreach($confessions as $confession)
                    @php($revision = $confession->revisions->sortByDesc('revision')->first())
                    <article class="feed-card">
                        <h2>{{ $revision?->title ?: __('lavbe.untitled_confession') }}</h2>
                        <p>{{ __('guest.device_status') }}: {{ __('state.'.($confession->status === 'unpublished' ? 'pending' : $confession->status)) }}</p>
                        <a href="{{ route('confessions.status', ['locale' => app(App\Support\Lavbe\LocaleRegistry::class)->urlSegment($confession->locale), 'publicId' => $confession->public_id]) }}">{{ __('guest.write_owner_link') }}</a>
                    </article>
                @endforeach
            </div>
            @if($confessions->hasPages())<x-pagination :paginator="$confessions" />@endif
        @endif
        @if($canForgetDevice)
            <details>
                <summary>{{ __('guest.device_forget') }}</summary>
                <form method="post" action="{{ route('confessions.device.forget', ['locale' => $lavbeLocale['segment']]) }}" class="device-forget-form">
                    @csrf
                    <p>{{ __('guest.device_forget_hint') }}</p>
                    <button class="button button-secondary" type="submit">{{ __('guest.device_forget') }}</button>
                </form>
            </details>
        @endif
    </section>
@endsection
