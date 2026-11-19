@foreach($confessions as $confession)
    <article class="story-card" lang="{{ $confession->locale }}" data-confession-id="{{ $confession->id }}">
        <h2 class="story-title">{{ $confession->title }}</h2>
        <p class="story-excerpt">{{ $confession->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($confession->body), 240) }}</p>
        <time datetime="{{ $confession->published_at->toIso8601String() }}">{{ $confession->published_at->toDateString() }}</time>
    </article>
@endforeach
