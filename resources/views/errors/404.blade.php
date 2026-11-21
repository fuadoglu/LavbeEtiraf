@extends('layouts.app')

@section('title', __('ui.missing').' · '.config('app.name'))

@section('content')
    <section class="not-found" aria-labelledby="not-found-title">
        <p class="not-found-code" aria-hidden="true">404</p>
        <h1 id="not-found-title">{{ __('ui.missing') }}</h1>
        <p class="muted">{{ __('ui.missing_body') }}</p>
        <a class="button" href="{{ route('home', ['locale' => $lavbeLocale['segment'] ?? 'en']) }}"><x-icon name="arrow-left" /> {{ __('ui.back') }}</a>
    </section>
@endsection
