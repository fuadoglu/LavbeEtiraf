@props(['topic', 'title', 'excerpt', 'continued' => false])
<article {{ $attributes->class(['story-card']) }}>
    <div class="story-meta">
        <span class="story-topic">{{ $topic }}</span>
        @if($continued)
            <span class="story-badge"><x-icon name="message-circle" class="icon-small" /> Davamı gəldi</span>
        @endif
    </div>
    <h2 class="story-title">{{ $title }}</h2>
    <p class="story-excerpt">{{ $excerpt }}</p>
</article>
