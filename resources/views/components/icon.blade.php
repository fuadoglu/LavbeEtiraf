@props(['name'])
@php
    $availableIcons = [
        'baby', 'arrow-left', 'bell', 'book-open', 'briefcase-business', 'calendar-days', 'chevron-down',
        'circle-check', 'circle-x', 'coffee', 'compass', 'feather', 'fingerprint', 'graduation-cap',
        'eye', 'heart', 'heart-handshake', 'heart-pulse', 'house', 'info', 'languages', 'list-filter',
        'menu', 'message-circle', 'moon', 'pen-line', 'route', 'search', 'share-2', 'settings-2', 'shield-check', 'sparkles', 'flame',
        'signpost', 'sun', 'triangle-alert', 'user-round', 'users-round', 'wallet', 'x',
    ];
    abort_unless(in_array($name, $availableIcons, true), 404);
@endphp
<span aria-hidden="true" {{ $attributes->class(['icon']) }} style="--icon-mask: url('{{ asset('icons/lucide/'.$name.'.svg') }}')"></span>
