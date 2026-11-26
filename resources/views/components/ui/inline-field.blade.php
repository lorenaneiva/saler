@props(['form', 'name', 'label', 'value' => '', 'type' => 'text', 'options' => null, 'required' => false])

<span data-inline-display>{{ $slot->isEmpty() ? ($value === null || $value === '' ? '—' : $value) : $slot }}</span>
<div data-inline-input hidden class="min-w-32 text-left font-normal">
    <label for="{{ $form }}-{{ $name }}" class="block text-xs font-medium">{{ $label }} @if ($required)<x-required-mark />@endif</label>
    @if ($options !== null)
        <select id="{{ $form }}-{{ $name }}" name="{{ $name }}" form="{{ $form }}" class="field" disabled
            @required($required) aria-describedby="{{ $form }}-{{ $name }}-error" {{ $attributes }}>
            @foreach ($options as $key => $text)
                <option value="{{ $key }}" @selected((string) $key === (string) $value)>{{ $text }}</option>
            @endforeach
        </select>
    @else
        <input id="{{ $form }}-{{ $name }}" name="{{ $name }}" form="{{ $form }}" type="{{ $type }}" value="{{ $value }}"
            class="field" disabled @required($required) aria-describedby="{{ $form }}-{{ $name }}-error" {{ $attributes }}>
    @endif
    <p id="{{ $form }}-{{ $name }}-error" data-inline-error="{{ $name }}" class="text-sm text-red-800" hidden></p>
</div>
