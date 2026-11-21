@extends('layouts.app')
@section('title', __('participation.supported_heading').' · '.config('app.name'))
@push('head')<meta name="robots" content="noindex,nofollow">@endpush
@section('content')
<section class="stack" aria-labelledby="supported-title">
    <h1 id="supported-title">{{ __('participation.supported_heading') }}</h1>
    @if($confessions->isEmpty())<p>{{ __('participation.supported_empty') }}</p>
    @else<div class="confession-feed feed-list"><x-feed-items :confessions="$confessions" /></div><x-pagination :paginator="$confessions" />@endif
</section>
@endsection
