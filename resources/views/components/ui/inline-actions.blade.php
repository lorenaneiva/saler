@props(['id', 'action', 'label' => 'Editar', 'text' => 'Editar'])

<button type="button" data-inline-start data-inline-display class="button-quiet button-small" aria-label="{{ $label }}">{{ $text }}</button>
<form id="{{ $id }}" method="POST" action="{{ $action }}" data-inline-form data-inline-input hidden class="grid gap-2">
    @csrf
    @method('PATCH')
    <div class="flex flex-wrap gap-2">
        <button type="submit" class="button-primary button-small">Salvar</button>
        <button type="button" data-inline-cancel class="button-secondary button-small">Cancelar</button>
    </div>
    <p data-inline-status role="alert" tabindex="-1" class="max-w-64 whitespace-normal text-sm text-red-800" hidden></p>
</form>
