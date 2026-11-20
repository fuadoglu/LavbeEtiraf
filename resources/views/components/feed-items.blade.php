@foreach($confessions as $confession)
    <article class="story-card" lang="{{ $confession->locale }}" data-confession-id="{{ $confession->id }}">
        @php($topic = app(App\Support\Lavbe\LocaleRegistry::class)->category((string) $confession->category_key))
        <div class="story-meta">
            @if($topic)<span class="story-topic"><x-icon :name="$topic['icon']" class="icon-small" />{{ $topic['labels'][app()->getLocale()] ?? $topic['labels']['en'] }}</span>@endif
            <time datetime="{{ $confession->published_at->toIso8601String() }}" lang="{{ app()->getLocale() }}">{{ $confession->published_at->locale(app()->getLocale())->translatedFormat('j M Y') }}</time>
        </div>
        <h2 class="story-title"><a href="{{ route('confessions.public', ['locale' => app(\App\Support\Lavbe\LocaleRegistry::class)->urlSegment($confession->locale), 'publicId' => $confession->public_id]) }}">{{ $confession->title ?: __('lavbe.untitled_confession') }}</a></h2>
        <p class="story-excerpt">{{ $confession->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($confession->body), 240) }}</p>
        @if($confession->public_comment_count > 0 || $confession->public_continuation_count > 0 || $confession->unique_read_count > 0)
        <footer class="story-card-footer">
            <p class="story-author"><x-icon name="user-round" class="icon-small" />{{ $confession->author_alias }}</p>
            @if($confession->public_comment_count > 0)
                <span class="story-activity"><x-icon name="message-circle" class="icon-small" />{{ __('participation.public_comments', ['count' => number_format((int) $confession->public_comment_count)]) }}</span>
            @endif
            @if($confession->public_continuation_count > 0)
                <span class="story-activity"><x-icon name="message-circle" class="icon-small" />{{ __('participation.has_continuation') }}</span>
            @endif
            @if($confession->unique_read_count > 0)
                <span class="unique-read-count" role="img" aria-label="{{ __('participation.unique_reads', ['count' => number_format((int) $confession->unique_read_count)]) }}"><x-icon name="eye" class="icon-small" /><span aria-hidden="true">{{ number_format((int) $confession->unique_read_count) }}</span></span>
            @endif
        </footer>
        @endif
    </article>
@endforeach
