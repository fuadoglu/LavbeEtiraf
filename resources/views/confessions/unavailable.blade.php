@extends('layouts.app')

@section('title', __('guest.write_unavailable').' · '.config('app.name'))

@section('content')
    <section class="write-page submission-result" role="status">
        <h1>{{ __('guest.write_unavailable') }}</h1>
    </section>
@endsection
