@extends('layouts.app')

@section('title', $copy['title'].' · Lavbe')

@push('head')
    <meta name="robots" content="noindex,nofollow">
@endpush

@section('content')
    <article class="reading-page" aria-labelledby="legal-title">
        <h1 id="legal-title">{{ $copy['title'] }}</h1>
        <p>{{ $copy['body'] }}</p>
        @if(in_array(request()->route('page'), ['privacy', 'data_request'], true))
            <p>{{ __('legal.rights') }}</p>
        @endif
        @if(in_array(request()->route('page'), ['privacy', 'data_request', 'operator'], true))
            @if(config('lavbe.operator_name'))
                <p>{{ __('legal.operator.title') }} · {{ config('lavbe.operator_name') }}</p>
            @endif
            @if(filter_var(config('lavbe.privacy_email'), FILTER_VALIDATE_EMAIL))
                <p>{{ __('legal.contact_label') }} · <a href="mailto:{{ config('lavbe.privacy_email') }}">{{ config('lavbe.privacy_email') }}</a></p>
            @endif
        @endif
    </article>
@endsection
