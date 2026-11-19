@props(['selected' => null])
@php
    $topics = config('lavbe.categories');
@endphp
<nav class="topic-panel" aria-label="Etirafın mövzusu">
    <a class="topic-chip" href="{{ route('home') }}" @if(!$selected) aria-current="page" @endif>Hamısı</a>
    @foreach($topics as $slug => $label)
        <a class="topic-chip" href="{{ route('topic.show', ['category' => $slug]) }}" @if($selected === $slug) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
