@props(['label', 'name', 'hint' => null, 'error' => null, 'id' => null])
@php($fieldId = $id ?? $name)
<div class="form-field">
    <label for="{{ $fieldId }}">{{ $label }}</label>
    {{ $slot->withAttributes(['id' => $fieldId, 'name' => $name, 'aria-describedby' => $hint || $error ? $fieldId.'-description' : null, 'aria-invalid' => $error ? 'true' : 'false']) }}
    @if($hint || $error)
        <p class="field-description" id="{{ $fieldId }}-description">{{ $error ?: $hint }}</p>
    @endif
</div>
