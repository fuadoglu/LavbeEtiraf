@props(['label', 'name', 'hint' => null, 'error' => null, 'id' => null])
@php
    $fieldId = $id ?? $name;
    $error = $error ?? $errors->first($name);
@endphp
<div class="form-field">
    <label for="{{ $fieldId }}">{{ $label }}</label>
    {{ $slot }}
    @if($hint || $error)
        <p class="field-description" id="{{ $fieldId }}-description">{{ $error ?? $hint }}</p>
    @endif
</div>
