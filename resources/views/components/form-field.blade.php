@props(['label', 'name', 'hint' => null, 'error' => false, 'id' => null])
@php
    $fieldId = $id ?? $name;
    $error = $error === false
        ? (old('_form_errors.'.$name) ?: $errors->first($name))
        : ($error === '' ? (old('_form_errors.'.$name) ?: '') : $error);
@endphp
<div class="form-field">
    <label for="{{ $fieldId }}">{{ $label }}</label>
    {{ $slot }}
    @if($hint || $error)
        <p class="field-description" id="{{ $fieldId }}-description">{{ $error ?: $hint }}</p>
    @endif
</div>
