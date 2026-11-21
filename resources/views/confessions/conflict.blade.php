@extends('layouts.app')

@section('title', __('guest.write_duplicate_title').' · '.config('app.name'))

@section('content')
    <section class="write-page submission-result">
        <h1>{{ __('guest.write_duplicate_title') }}</h1>
        <p>{{ __('guest.write_conflict_body') }}</p>
    </section>
@endsection
