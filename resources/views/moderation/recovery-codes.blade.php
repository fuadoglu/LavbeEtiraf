@extends('layouts.app')

@section('title', __('moderation.recovery_title'))

@section('content')
    <section class="write-page">
        <h1>{{ __('moderation.recovery_title') }}</h1>
        <p>{{ __('moderation.recovery_body') }}</p>
        <ul>@foreach($codes as $code)<li><code dir="ltr">{{ $code }}</code></li>@endforeach</ul>
        <a class="button button-primary" href="{{ route('moderation.queue', ['locale' => request()->route('locale')]) }}">{{ __('moderation.queue_title') }}</a>
    </section>
@endsection
