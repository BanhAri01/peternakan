@props(['label' => null, 'name' => null, 'required' => false, 'optional' => false, 'hint' => null, 'for' => null])

@php
    // name "grades[0][kg]" -> kunci error "grades.0.kg"
    $errorKey = $name ? trim(str_replace(['[', ']'], ['.', ''], $name), '.') : null;
@endphp

<div {{ $attributes->class('field') }}>
    @if($label)
        <label class="field-label" @if($for ?? $name) for="{{ $for ?? $name }}" @endif>
            {{ $label }}@if($required)<span class="req" aria-hidden="true">*</span>@endif
            @if($optional)<span class="opt">(boleh kosong)</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if($hint)
        <div class="field-hint">{{ $hint }}</div>
    @endif

    @if($errorKey && $errors->has($errorKey))
        <div class="field-error"><i class="bi bi-exclamation-circle-fill"></i> {{ $errors->first($errorKey) }}</div>
    @endif
</div>
