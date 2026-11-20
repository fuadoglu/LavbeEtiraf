@extends('layouts.app')

@section('title', $copy['title'].' · Lavbe')

@push('head')
    <meta name="robots" content="noindex,nofollow">
@endpush

@section('content')
    <article class="reading-page" aria-labelledby="legal-title">
        <h1 id="legal-title">{{ $copy['title'] }}</h1>
        <p>{{ $copy['body'] }}</p>
    </article>
@endsection
