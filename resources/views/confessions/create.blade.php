@extends('layouts.app')

@section('title', __('guest.write_title').' · '.config('app.name'))

@section('content')
    <section class="write-page">
        <header class="write-heading">
            <h1>{{ __('guest.write_title') }}</h1>
            <p class="write-anonymous-note"><x-icon name="shield-check" class="icon-small" />{{ __('guest.write_anonymous') }}</p>
        </header>

        @include('confessions._form', ['formAction' => route('confessions.store', ['locale' => $lavbeLocale['segment']])])
    </section>
@endsection
