@extends('layouts.app')

@section('title', __('guest.write_duplicate_title').' · '.config('app.name'))

@section('content')
    <section class="write-page submission-result">
        <h1>{{ __('guest.write_duplicate_title') }}</h1>
        <p>{{ __('guest.write_duplicate_body') }}</p>
        @if(request()->cookie('lavbe_owner_'.$confession->public_id))
            <a href="{{ route('confessions.status', ['locale' => $lavbeLocale['segment'], 'publicId' => $confession->public_id]) }}">{{ __('guest.write_owner_link') }}</a>
        @endif
    </section>
@endsection
