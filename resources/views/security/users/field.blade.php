@php
    $id ??= 'new-user-'.str_replace('_', '-', $field);
    $error = $errors->getBag($bag)->first($field === 'password_confirmation' ? 'password_confirmation' : $field);
    $value = $type === 'password' ? '' : old($field);
@endphp
<div class="grid content-start gap-2">
    <label for="{{ $id }}" class="font-medium">{{ $label }} <x-required-mark /></label>
    <input id="{{ $id }}" name="{{ $field }}" type="{{ $type }}" class="field" required maxlength="255" autocomplete="{{ $autocomplete }}"
        value="{{ is_string($value) ? $value : '' }}"
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif (isset($hint)) aria-describedby="{{ $id }}-hint" @endif>
    @if ($error)
        <p id="{{ $id }}-error" role="alert" class="text-sm text-red-800">{{ $error }}</p>
    @elseif (isset($hint))
        <p id="{{ $id }}-hint" class="text-xs text-slate-600">{{ $hint }}</p>
    @endif
</div>
