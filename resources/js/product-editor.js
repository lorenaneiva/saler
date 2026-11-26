import { formatProductPrice, parseProductPrice, productMargin } from './product-pricing';
import { focusFormControl, initializeAutocompletes } from './autocomplete';

function preparePricing(form) {
    const price = form.elements.sale_price;
    const margin = form.querySelector('[data-product-margin]');
    const initial = price.value;
    price.type = 'text';
    price.inputMode = 'decimal';
    price.value = formatProductPrice(initial);

    function update() {
        const value = parseProductPrice(price.value);
        price.setCustomValidity(value === null ? 'Informe um preço válido, como 1.234,56.' : '');
        margin.textContent = productMargin(value, form.dataset.averageCost);
    }

    price.addEventListener('input', update);
    price.addEventListener('blur', () => {
        const value = parseProductPrice(price.value);
        if (value !== null) {
            price.value = formatProductPrice(value);
        }
    });
    update();
}

export function initializeProductEditor(closeDialog) {
    document.querySelectorAll('[data-product-form]').forEach((form) => {
        preparePricing(form);
        form.addEventListener('submit', () => {
            form.elements.sale_price.value = parseProductPrice(form.elements.sale_price.value);
        });
    });

    const drawer = document.getElementById('product-editor');
    if (!drawer) {
        return;
    }

    const content = drawer.querySelector('[data-product-editor-content]');
    const discardDialog = document.getElementById('discard-product-changes');
    const feedback = document.querySelector('[data-product-feedback]');
    const title = drawer.querySelector('#product-editor-title');
    let form;
    let original = '';
    let saving = false;
    let loader;
    let openerUrl;
    let scrollPosition;
    let previousOverflow;

    function values() {
        if (!form) {
            return '';
        }

        return JSON.stringify(['name', 'barcode', 'category_id', 'supplier_id', 'sale_price'].map((key) => {
            const value = form.elements.namedItem(key).value;
            return key === 'sale_price' ? (parseProductPrice(value) ?? value) : value;
        }));
    }

    const dirty = () => form && values() !== original;

    function showFeedback(message, retry = false) {
        feedback.replaceChildren(document.createTextNode(message));
        feedback.classList.toggle('notice-success', !retry);
        feedback.classList.toggle('notice-error', retry);
        if (retry) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'button-secondary button-small';
            button.textContent = 'Atualizar listagem';
            button.addEventListener('click', async () => {
                button.disabled = true;
                try {
                    await refreshList();
                    showFeedback('Listagem atualizada.');
                } catch {
                    button.disabled = false;
                }
            });
            feedback.append(button);
        }
        feedback.hidden = false;
    }

    async function refreshList() {
        const response = await fetch(window.location.href, { headers: { Accept: 'text/html' }, cache: 'no-store' });
        if (!response.ok || response.redirected) {
            throw new Error('Não foi possível atualizar a listagem.');
        }
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const replacement = page.querySelector('[data-product-list]');
        if (!replacement) {
            throw new Error('Listagem indisponível.');
        }

        const current = document.querySelector('[data-product-list]');
        const horizontalScroll = current.querySelector('.table-region')?.scrollLeft ?? 0;
        const position = { left: window.scrollX, top: window.scrollY };
        replacement.style.minHeight = `${current.getBoundingClientRect().height}px`;
        current.replaceWith(replacement);
        const table = replacement.querySelector('.table-region');
        if (table) {
            table.scrollLeft = horizontalScroll;
        }
        window.scrollTo({ ...position, behavior: 'instant' });
    }

    function showError(message, errors = {}) {
        const summary = form.querySelector('[data-product-error]');
        summary.textContent = message;
        summary.hidden = false;
        form.querySelectorAll('[data-product-field-error]').forEach((element) => {
            const messages = errors[element.dataset.productFieldError];
            element.textContent = messages?.join(' ') ?? '';
            element.hidden = !messages?.length;
            if (messages?.length) {
                form.elements.namedItem(element.dataset.productFieldError).setAttribute('aria-invalid', 'true');
            }
        });
    }

    async function submit(event) {
        event.preventDefault();
        if (saving) {
            return;
        }

        form.querySelector('[data-product-error]').hidden = true;
        form.querySelectorAll('[aria-invalid]').forEach((field) => field.removeAttribute('aria-invalid'));
        form.querySelectorAll('[data-product-field-error]').forEach((error) => { error.hidden = true; });
        const body = new FormData(form);
        body.set('sale_price', parseProductPrice(form.elements.sale_price.value));
        saving = true;
        form.setAttribute('aria-busy', 'true');
        const controls = [...form.querySelectorAll('input, select, button')];
        controls.forEach((field) => { field.disabled = true; });
        const save = form.querySelector('[data-product-save]');
        save.setAttribute('data-busy', '');

        try {
            const response = await fetch(form.action, { method: 'POST', body, headers: { Accept: 'application/json' } });
            if (response.status === 422) {
                const result = await response.json();
                showError('Revise os campos indicados.', result.errors);
            } else if (response.status === 401 || response.status === 419) {
                showError('Sua sessão expirou. Entre novamente em outra aba e tente salvar.');
            } else if (!response.ok) {
                showError('Não foi possível salvar. Os dados digitados foram mantidos.');
            } else {
                const result = await response.json();
                original = values();
                try {
                    await refreshList();
                    showFeedback(result.message);
                } catch {
                    showFeedback(`${result.message} Não foi possível atualizar a listagem.`, true);
                }
                saving = false;
                closeDialog(drawer);
                return;
            }
        } catch {
            showError('Não foi possível confirmar o salvamento. Verifique a conexão antes de tentar novamente.');
        } finally {
            saving = false;
            form.removeAttribute('aria-busy');
            controls.forEach((field) => { field.disabled = false; });
            save.removeAttribute('data-busy');
        }

        focusFormControl(form.querySelector('[aria-invalid]') ?? form.querySelector('[data-product-error]'));
    }

    document.addEventListener('click', async (event) => {
        const opener = event.target.closest('[data-product-open]');
        if (!opener || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) {
            return;
        }
        event.preventDefault();
        if (drawer.open) {
            return;
        }

        openerUrl = opener.href;
        scrollPosition = { left: window.scrollX, top: window.scrollY };
        previousOverflow = document.documentElement.style.overflow;
        document.documentElement.style.overflow = 'hidden';
        form = null;
        original = '';
        feedback.hidden = true;
        title.textContent = opener.closest('[data-product-list]') ? 'Editar produto' : 'Novo produto';
        const loading = document.createElement('p');
        loading.className = 'p-5 text-sm';
        loading.setAttribute('role', 'status');
        loading.textContent = 'Carregando produto…';
        content.replaceChildren(loading);
        drawer.showModal();
        content.setAttribute('aria-busy', 'true');
        loader = new AbortController();

        try {
            const response = await fetch(openerUrl, { headers: { Accept: 'application/json' }, signal: loader.signal, cache: 'no-store' });
            if (!response.ok) {
                throw new Error('Não foi possível carregar o produto. Feche o painel e tente novamente.');
            }
            const result = await response.json();
            content.innerHTML = result.html;
            initializeAutocompletes(content);
            form = content.querySelector('[data-product-form]');
            if (form) {
                preparePricing(form);
                original = values();
                form.addEventListener('submit', submit);
                form.querySelector('[data-product-cancel]').addEventListener('click', (cancelEvent) => {
                    cancelEvent.preventDefault();
                    closeDialog(drawer);
                });
                form.elements.namedItem('name').focus({ preventScroll: true });
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                loading.textContent = 'Não foi possível carregar o produto. Feche o painel e tente novamente.';
                loading.setAttribute('role', 'alert');
            }
        } finally {
            content.removeAttribute('aria-busy');
        }
    });

    drawer.addEventListener('dialog:before-close', (event) => {
        if (saving) {
            event.preventDefault();
        } else if (dirty()) {
            event.preventDefault();
            if (!discardDialog.open) {
                discardDialog.showModal();
            }
        }
    });
    discardDialog.querySelector('[data-product-discard]').addEventListener('click', () => {
        original = values();
        discardDialog.addEventListener('close', () => closeDialog(drawer), { once: true });
        closeDialog(discardDialog);
    });
    drawer.addEventListener('close', () => {
        loader?.abort();
        document.documentElement.style.overflow = previousOverflow;
        const opener = [...document.querySelectorAll('[data-product-open]')].find((link) => link.href === openerUrl);
        (opener ?? document.querySelector('[data-product-open]'))?.focus({ preventScroll: true });
        window.scrollTo({ ...scrollPosition, behavior: 'instant' });
    });
    window.addEventListener('beforeunload', (event) => {
        if (drawer.open && (dirty() || saving)) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
}
