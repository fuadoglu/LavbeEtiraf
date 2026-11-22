@extends('layouts.app')

@section('title', __('reports.received_title').' · '.config('app.name'))

@section('content')
    <section class="write-page submission-result">
        <h1>{{ __('reports.received_title') }}</h1>
        <p>{{ __($outcome === 'duplicate' ? 'reports.received_duplicate' : ($outcome === 'updated' ? 'reports.received_updated' : 'reports.received_'.($channel === 'legal' ? 'legal' : 'community'))) }}</p>
        <a href="{{ route('home', ['locale' => $lavbeLocale['segment']]) }}">{{ __('reports.back_to_story') }}</a>
    </section>
@endsection
