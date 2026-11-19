@extends('layouts.app')

@php
    $topicLabels = config('lavbe.categories');
    $sortLabels = config('lavbe.feed_orders');
@endphp

@section('title', ($category ? $topicLabels[$category] ?? 'Mövzu' : 'Etiraflar lenti').' · Lavbe')

@section('content')
    <section aria-labelledby="feed-title">
        <div class="page-heading">
            <h1 id="feed-title">{{ $category ? $topicLabels[$category] ?? 'Mövzu' : 'Etiraflar Vadisi' }}</h1>
            <p>Oxu, dinlə və özünü tək hiss etmə.</p>
        </div>

        <div class="feed-controls">
            <x-topic-panel :selected="$category" />
            <nav class="sort-panel" aria-label="Lentin sıralanması">
                @foreach($sortLabels as $key => $label)
                    @php
                        $href = $category
                            ? route('topic.show', ['category' => $category, 'sort' => $key])
                            : route('home', ['sort' => $key]);
                    @endphp
                    <a class="sort-link" href="{{ $href }}" @if($sort === $key) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
        </div>

        <div class="empty-state">
            <x-icon name="book-open" class="icon-large" />
            <h2>Hələ dərc olunmuş etiraf yoxdur</h2>
            <p>Təsdiqlənmiş hekayələr öz dilində burada görünəcək. Bu lent başqa dildən məzmun göstərmir.</p>
        </div>
    </section>
@endsection
