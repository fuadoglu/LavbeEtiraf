@extends('layouts.app')

@section('title', 'Səhifə tapılmadı · Lavbe')

@section('content')
    <section class="not-found" aria-labelledby="not-found-title">
        <p class="not-found-code" aria-hidden="true">404</p>
        <h1 id="not-found-title">Bu səhifə tapılmadı</h1>
        <p class="muted">Ünvan dəyişmiş və ya səhifə artıq mövcud olmaya bilər.</p>
        <a class="button" href="{{ route('home') }}"><x-icon name="arrow-left" /> Oxu lentinə qayıt</a>
    </section>
@endsection
