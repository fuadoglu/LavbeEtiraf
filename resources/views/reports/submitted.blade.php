@extends('layouts.app')

@section('title', __('reports.received_title').' · Lavbe')

@section('content')
    <section class="write-page submission-result">
        <h1>{{ __('reports.received_title') }}</h1>
        <p>{{ __('reports.received_'.($channel === 'legal' ? 'legal' : 'community')) }}</p>
        <a href="{{ route('home', ['locale' => $lavbeLocale['segment']]) }}">{{ __('reports.back_to_story') }}</a>
    </section>
@endsection
