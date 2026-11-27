<div data-product-feedback class="product-feedback notice notice-success" role="status" aria-live="polite" hidden></div>

<x-ui.dialog id="product-editor" title="Editar produto" drawer class="product-editor">
    <div data-product-editor-content></div>
</x-ui.dialog>

<x-ui.dialog id="discard-product-changes" title="Descartar alterações?">
    <p class="text-sm">As alterações deste produto ainda não foram salvas.</p>
    <div class="flex flex-wrap gap-2">
        <button type="button" class="button-secondary" data-dialog-close autofocus>Continuar editando</button>
        <button type="button" class="button-danger" data-product-discard>Descartar alterações</button>
    </div>
</x-ui.dialog>
