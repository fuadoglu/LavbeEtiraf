@foreach($confessions as $confession)
    <article class="story-card" lang="{{ $confession->locale }}" data-confession-id="{{ $confession->id }}">
        @php($topic = app(App\Support\Lavbe\LocaleRegistry::class)->category((string) $confession->category_key))
        <div class="story-meta">
            @if($topic)<span class="story-topic">{{ $topic['labels'][app()->getLocale()] ?? $topic['labels']['en'] }}</span>@endif
            <time datetime="{{ $confession->published_at->toIso8601String() }}">{{ $confession->published_at->toDateString() }}</time>
        </div>
        <h2 class="story-title"><a href="{{ route('confessions.public', ['locale' => $confession->locale, 'publicId' => $confession->public_id]) }}">{{ $confession->title ?: __('lavbe.untitled_confession') }}</a></h2>
        <p class="story-excerpt">{{ $confession->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($confession->body), 240) }}</p>
        <p class="story-author"><x-icon name="user-round" class="icon-small" />{{ $confession->author_alias }}</p>
    </article>
@endforeach
