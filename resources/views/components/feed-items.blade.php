@foreach($confessions as $confession)
    <article class="story-card" lang="{{ $confession->locale }}" data-confession-id="{{ $confession->id }}">
        <h2 class="story-title"><a href="{{ route('confessions.public', ['locale' => $confession->locale, 'publicId' => $confession->public_id]) }}">{{ $confession->title ?: __('lavbe.untitled_confession') }}</a></h2>
        <p class="story-excerpt">{{ $confession->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($confession->body), 240) }}</p>
        <time datetime="{{ $confession->published_at->toIso8601String() }}">{{ $confession->published_at->toDateString() }}</time>
    </article>
@endforeach
