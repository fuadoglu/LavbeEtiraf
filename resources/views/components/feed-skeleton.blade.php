@props(['count' => 3])
<div class="feed-list" aria-label="Məzmun yüklənir" aria-busy="true">
    @for($index = 0; $index < $count; $index++)
        <article class="story-card skeleton-card" aria-hidden="true">
            <span class="skeleton-line skeleton-short"></span>
            <span class="skeleton-line skeleton-title"></span>
            <span class="skeleton-line"></span>
            <span class="skeleton-line skeleton-medium"></span>
        </article>
    @endfor
</div>
