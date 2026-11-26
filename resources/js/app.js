import { initializeProductEditor } from './product-editor';
import { focusFormControl, initializeAutocompletes, syncAutocomplete } from './autocomplete';
import { initializeLiveSearch } from './live-search';

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const confirmations = new WeakSet();

initializeAutocompletes();
initializeLiveSearch();

document.querySelectorAll('[data-inline-editor]').forEach((editor) => {
    const form = editor.querySelector('[data-inline-form]');
    const start = editor.querySelector('[data-inline-start]');
    const cancel = editor.querySelector('[data-inline-cancel]');
    const status = editor.querySelector('[data-inline-status]');
    const fields = Array.from(form.elements).filter((field) => field.matches('input:not([type="hidden"]), select, textarea'));
    let saving = false;

    function clearErrors() {
        status.hidden = true;
        status.textContent = '';
        fields.forEach((field) => field.removeAttribute('aria-invalid'));
        editor.querySelectorAll('[data-inline-error]').forEach((error) => {
            error.textContent = '';
            error.hidden = true;
        });
    }

    function toggleEditing(editing) {
        editor.querySelectorAll('[data-inline-display]').forEach((element) => { element.hidden = editing; });
        editor.querySelectorAll('[data-inline-input]').forEach((element) => { element.hidden = !editing; });
        fields.forEach((field) => { field.disabled = !editing; });
        document.querySelectorAll('[data-inline-start]').forEach((button) => { button.disabled = editing; });
        clearErrors();
        if (editing) {
            focusFormControl(fields[0]);
        } else {
            start.focus();
        }
    }

    start.addEventListener('click', () => toggleEditing(true));
    cancel.addEventListener('click', () => {
        if (!saving) {
            form.reset();
            toggleEditing(false);
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (saving) {
            return;
        }

        clearErrors();
        const body = new FormData(form);
        saving = true;
        form.setAttribute('aria-busy', 'true');
        form.querySelectorAll('button').forEach((button) => { button.disabled = true; });
        fields.forEach((field) => { field.disabled = true; });

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body,
                headers: { Accept: 'application/json' },
            });
            if (response.ok) {
                window.location.reload();
                return;
            }

            if (response.status === 422) {
                const result = await response.json();
                editor.querySelectorAll('[data-inline-error]').forEach((error) => {
                    const messages = result.errors?.[error.dataset.inlineError];
                    if (messages?.length) {
                        error.textContent = messages.join(' ');
                        error.hidden = false;
                        form.elements.namedItem(error.dataset.inlineError)?.setAttribute('aria-invalid', 'true');
                    }
                });
                status.textContent = 'Revise os campos indicados antes de salvar.';
            } else if (response.status === 401 || response.status === 419) {
                status.textContent = 'Sua sessão expirou. Abra o sistema em outra aba, entre novamente e tente salvar.';
            } else {
                status.textContent = 'Não foi possível salvar. Seus dados foram mantidos; tente novamente.';
            }
        } catch {
            status.textContent = 'Não foi possível confirmar o salvamento. Verifique sua conexão e tente novamente.';
        } finally {
            saving = false;
            form.removeAttribute('aria-busy');
            form.querySelectorAll('button').forEach((button) => { button.disabled = false; });
            fields.forEach((field) => { field.disabled = false; });
        }

        status.hidden = false;
        focusFormControl(fields.find((field) => field.hasAttribute('aria-invalid')) ?? status);
    });
});

function closeDialog(dialog) {
    if (dialog.hasAttribute('data-closing')) {
        return;
    }

    if (!dialog.dispatchEvent(new CustomEvent('dialog:before-close', { cancelable: true }))) {
        return;
    }

    if (reducedMotion.matches) {
        dialog.close();
        return;
    }

    dialog.setAttribute('data-closing', '');
    const finish = () => {
        dialog.removeAttribute('data-closing');
        if (dialog.open) {
            dialog.close();
        }
    };

    Promise.allSettled(dialog.getAnimations().map((animation) => animation.finished)).then(finish);
}

initializeProductEditor(closeDialog);

document.querySelectorAll('dialog').forEach((dialog) => {
    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        closeDialog(dialog);
    });

    dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) {
            return;
        }

        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) {
            closeDialog(dialog);
        }
    });

    if (dialog.hasAttribute('data-auto-open')) {
        dialog.showModal();
    }
});

document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-dialog-open]');
    if (opener) {
        document.getElementById(opener.dataset.dialogOpen)?.showModal();
    }

    const closer = event.target.closest('[data-dialog-close]');
    if (closer) {
        closeDialog(closer.closest('dialog'));
    }
});

const confirmDialog = document.getElementById('confirm-operation');
let confirmedForm;
let confirmedSubmitter;

confirmDialog?.querySelector('[data-confirm-submit]').addEventListener('click', () => {
    const form = confirmedForm;
    const submitter = confirmedSubmitter;

    if (!form) {
        return;
    }

    closeDialog(confirmDialog);
    confirmations.add(form);
    form.requestSubmit(submitter ?? undefined);
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented) {
        return;
    }

    if (form.hasAttribute('data-submitting')) {
        event.preventDefault();
        return;
    }

    if (form.dataset.confirm && !confirmations.delete(form) && confirmDialog) {
        event.preventDefault();
        confirmedForm = form;
        confirmedSubmitter = event.submitter;
        confirmDialog.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
        confirmDialog.showModal();
        return;
    }

    if (form.method.toLowerCase() === 'post') {
        form.setAttribute('data-submitting', '');
        form.setAttribute('aria-busy', 'true');
        event.submitter?.setAttribute('data-busy', '');
    }
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-busy], [data-submitting]').forEach((element) => {
        element.removeAttribute('data-busy');
        element.removeAttribute('data-submitting');
        element.removeAttribute('aria-busy');
    });
});

function toCents(value) {
    if (!/^\d+(?:\.\d{0,2})?$/.test(value)) {
        return null;
    }

    const [whole, fraction = ''] = value.split('.');
    return BigInt(whole) * 100n + BigInt(fraction.padEnd(2, '0'));
}

document.querySelectorAll('[data-payment-preview]').forEach((preview) => {
    const input = preview.form.elements.amount;
    const balance = toCents(preview.dataset.paymentPreview);
    const whole = new Intl.NumberFormat('pt-BR');

    function update() {
        const value = toCents(input.value);
        const remaining = balance - (value ?? 0n);
        preview.textContent = remaining < 0n
            ? 'O valor informado supera o saldo em aberto.'
            : `Saldo após pagamento: R$ ${whole.format(remaining / 100n)},${String(remaining % 100n).padStart(2, '0')}`;
    }

    input.addEventListener('input', update);
    update();
});

// O leitor em modo teclado pode enviar Enter ao concluir a leitura.
document.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && !event.isComposing && event.target.matches?.('[data-barcode-input]')) {
        event.preventDefault();
        event.target.select();
    }
});

document.querySelectorAll('[data-mask]').forEach((input) => {
    input.addEventListener('blur', () => {
        const digits = input.value.replace(/\D/g, '');
        if (input.dataset.mask === 'cpf' && /^[\d.\-\s]+$/.test(input.value) && digits.length === 11) {
            input.value = digits.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
        }

        if (input.dataset.mask === 'phone' && /^[\d()\-\s]+$/.test(input.value) && [10, 11].includes(digits.length)) {
            input.value = digits.replace(/^(\d{2})(\d{4,5})(\d{4})$/, '($1) $2-$3');
        }
    });
});

document.querySelectorAll('[data-line-editor]').forEach((editor) => {
    const items = editor.querySelector('[data-line-items]');
    const addItem = editor.querySelector('[data-line-add]');
    const template = editor.querySelector('[data-line-template]');

    function updateRows() {
        const rows = Array.from(items.children);

        rows.forEach((row, index) => {
            row.querySelectorAll('[data-item-field]').forEach((field) => {
                field.name = `items[${index}][${field.dataset.itemField}]`;
            });

            const productSelect = row.querySelector('[data-product-select]');
            if (productSelect) {
                const productFields = row.querySelector('[data-new-product-fields]');
                const isNewProduct = productSelect.value === 'new';
                if (isNewProduct) {
                    productSelect.removeAttribute('name');
                } else {
                    productSelect.name = `items[${index}][product_id]`;
                }

                row.querySelector('[data-create-product]').hidden = isNewProduct;
                productFields.hidden = !isNewProduct;
                productFields.disabled = !isNewProduct;
                productFields.querySelectorAll('[data-new-product-field]').forEach((field) => {
                    field.name = `items[${index}][new_product][${field.dataset.newProductField}]`;
                });
                syncAutocomplete(productSelect);
            }

            row.querySelector('[data-remove-item]').disabled = rows.length === 1;
        });

        addItem.disabled = rows.length >= 50;
    }

    addItem.addEventListener('click', () => {
        items.append(template.content.cloneNode(true));
        initializeAutocompletes(items.lastElementChild);
        updateRows();
        focusFormControl(items.lastElementChild.querySelector('select'));
    });

    items.addEventListener('change', (event) => {
        if (event.target.matches('[data-product-select]')) {
            updateRows();
        }
    });

    items.addEventListener('click', (event) => {
        const createProduct = event.target.closest('[data-create-product]');
        if (createProduct) {
            const row = createProduct.closest('[data-item-row]');
            row.querySelector('[data-product-select]').value = 'new';
            updateRows();
            row.querySelector('[data-new-product-field="name"]').focus();
            return;
        }

        const remover = event.target.closest('[data-remove-item]');
        if (remover && items.children.length > 1) {
            remover.closest('[data-item-row]').remove();
            updateRows();
            addItem.focus();
        }
    });

    updateRows();
});
