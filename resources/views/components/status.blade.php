@props(['tone' => 'info', 'title' => null])
@php($icon = ['success' => 'circle-check', 'warning' => 'triangle-alert', 'error' => 'circle-x'][$tone] ?? 'info')
<div role="status" {{ $attributes->class(['status', 'status-'.$tone]) }}>
    <x-icon :name="$icon" />
    <div>
        @if($title)<strong>{{ $title }}</strong>@endif
        <div>{{ $slot }}</div>
    </div>
</div>
