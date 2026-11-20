@extends('layouts.app')

@section('title', __("reports.{$channel}_title").' · Lavbe')

@section('content')
    <section class="write-page">
        <header class="page-heading">
            <h1>{{ __("reports.{$channel}_title") }}</h1>
            <p>{{ __('reports.'.($comment ? 'target_comment' : 'target_confession')) }}: {{ $comment ? $confession->author_alias : ($confession->title ?: __('lavbe.untitled_confession')) }}</p>
        </header>
        @if($channel === 'legal')
            <x-status tone="warning">{{ __('reports.legal_separation') }}</x-status>
        @endif
        @include('reports.form', ['locale' => $lavbeLocale['segment']])
        @if($channel === 'community')
            <a href="{{ route('reports.legal.create', ['locale' => $lavbeLocale['segment'], 'type' => $targetType, 'targetPublicId' => $targetPublicId]) }}">{{ __('reports.legal_title') }}</a>
        @endif
    </section>
@endsection
