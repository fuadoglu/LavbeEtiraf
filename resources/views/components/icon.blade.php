@props(['name'])
@php
    $availableIcons = [
        'arrow-left', 'book-open', 'chevron-down', 'circle-check', 'circle-x', 'feather',
        'heart', 'info', 'languages', 'list-filter', 'menu', 'message-circle',
        'moon', 'pen-line', 'search', 'settings-2', 'shield-check', 'sun',
        'triangle-alert', 'user-round', 'x',
    ];
    abort_unless(in_array($name, $availableIcons, true), 404);
@endphp
<span aria-hidden="true" {{ $attributes->class(['icon']) }} style="--icon-mask: url('{{ asset('icons/lucide/'.$name.'.svg') }}')"></span>
