@props(['id', 'title'])
<dialog id="{{ $id }}" class="dialog" aria-labelledby="{{ $id }}-title">
    <div class="dialog-panel">
        <header class="dialog-header">
            <h2 id="{{ $id }}-title">{{ $title }}</h2>
            <button class="icon-button" type="button" data-dialog-close aria-label="Pəncərəni bağla"><x-icon name="x" /></button>
        </header>
        <div class="dialog-content">{{ $slot }}</div>
    </div>
</dialog>
