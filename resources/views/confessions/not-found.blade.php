@extends('layouts.app')

@section('title', __('guest.write_not_found').' · '.config('app.name'))

@section('content')
    <section class="write-page submission-result" role="status">
        <h1>{{ __('guest.write_not_found') }}</h1>
    </section>
@endsection
