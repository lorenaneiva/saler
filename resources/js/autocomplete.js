import { filterAutocompleteOptions, nextAutocompleteIndex } from './autocomplete-search';

const instances = new Map();
let active;
let sequence = 0;
let observer;

function element(tag, className, attributes = {}) {
    const node = document.createElement(tag);
    node.className = className;
    Object.entries(attributes).forEach(([name, value]) => node.setAttribute(name, value));
    return node;
}

class Autocomplete {
    constructor(source) {
        this.source = source;
        this.opened = false;
        this.activeIndex = -1;
        this.options = [];
        this.requestVersion = 0;
        this.animationVersion = 0;
        this.nextPage = null;
        const id = source.id || `autocomplete-${++sequence}`;
        const labels = [...source.labels];
        const label = labels.map((node) => {
            const copy = node.cloneNode(true);
            copy.querySelectorAll('select, input, button, [aria-hidden="true"]').forEach((child) => child.remove());
            return copy.textContent.trim();
        }).join(' ');
        this.wrapper = element('span', 'autocomplete');
        this.input = element('input', `${source.className} autocomplete-input`, {
            id, type: 'text', role: 'combobox', 'aria-autocomplete': 'list', 'aria-expanded': 'false',
            'aria-controls': `${id}-options`, autocomplete: 'off', autocapitalize: 'none', spellcheck: 'false', maxlength: '100',
        });
        this.clear = element('button', 'autocomplete-clear', { type: 'button', tabindex: '-1', 'aria-label': `Limpar ${label || 'seleção'}` });
        this.clear.textContent = '×';
        this.arrow = element('button', 'autocomplete-toggle', { type: 'button', tabindex: '-1', 'aria-label': `Mostrar opções: ${label || 'seleção'}` });
        this.arrow.innerHTML = '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        this.popup = element('div', 'autocomplete-popup');
        this.list = element('div', 'autocomplete-options', { id: `${id}-options`, role: 'listbox', 'aria-label': label || 'Opções' });
        this.message = element('p', 'autocomplete-message');
        this.more = element('button', 'autocomplete-more', { type: 'button', tabindex: '-1' });
        this.more.textContent = 'Carregar mais opções';
        this.live = element('span', 'sr-only', { role: 'status', 'aria-live': 'polite', 'aria-atomic': 'true' });
        this.popup.append(this.list, this.message, this.more);
        this.popup.hidden = true;
        source.before(this.wrapper);
        this.wrapper.append(source, this.input, this.clear, this.arrow, this.live);
        if (typeof this.popup.showPopover === 'function') {
            this.popup.popover = 'manual';
            this.wrapper.append(this.popup);
        } else {
            (source.closest('dialog') ?? document.body).append(this.popup);
        }
        source.id = `${id}-source`;
        labels.forEach((node) => { node.htmlFor = id; });
        source.hidden = true;
        source.tabIndex = -1;
        source.setAttribute('aria-hidden', 'true');
        source.setAttribute('data-autocomplete-source', '');
        this.sync();

        this.input.addEventListener('focus', () => this.open());
        this.input.addEventListener('click', () => { if (!this.opened) { this.open(); } });
        this.input.addEventListener('input', () => {
            this.input.setCustomValidity('');
            if (this.input.value === '' && !source.required) {
                if (![...source.options].some((option) => option.value === '')) { source.add(new Option('', '')); }
                source.value = '';
                source.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (!this.opened) { this.open(false); }
            this.search(this.input.value);
        });
        this.input.addEventListener('keydown', (event) => this.keydown(event));
        this.wrapper.addEventListener('focusout', () => {
            setTimeout(() => {
                if (!this.wrapper.contains(document.activeElement) && !this.popup.contains(document.activeElement)) { this.close(); }
            }, 0);
        });
        this.arrow.addEventListener('pointerdown', (event) => event.preventDefault());
        this.arrow.addEventListener('click', () => {
            if (this.opened) { this.close(); } else { this.input.focus({ preventScroll: true }); this.open(); }
        });
        this.clear.addEventListener('pointerdown', (event) => event.preventDefault());
        this.clear.addEventListener('click', () => { this.choose({ value: '', label: '' }); this.input.focus({ preventScroll: true }); this.close(); });
        this.popup.addEventListener('pointerdown', (event) => event.preventDefault());
        this.list.addEventListener('pointermove', (event) => {
            const option = event.target.closest('[data-option-index]');
            if (option) { this.highlight(Number(option.dataset.optionIndex), false); }
        });
        this.list.addEventListener('click', (event) => {
            const option = event.target.closest('[data-option-index]');
            if (option) { this.choose(this.options[Number(option.dataset.optionIndex)]); }
        });
        this.more.addEventListener('click', () => this.load(this.nextPage));
        this.popup.addEventListener('scroll', () => {
            if (this.nextPage && !this.loading && !this.failed && this.popup.scrollTop + this.popup.clientHeight >= this.popup.scrollHeight - 32) { this.load(this.nextPage); }
        });
        source.addEventListener('change', () => this.sync());
        source.addEventListener('invalid', (event) => {
            event.preventDefault();
            this.input.setCustomValidity(source.validationMessage);
            this.input.focus();
            this.input.reportValidity();
        });
    }

    sync() {
        const { source, input } = this;
        input.disabled = source.matches(':disabled');
        input.required = source.required;
        this.clear.disabled = input.disabled;
        this.arrow.disabled = input.disabled;
        this.clear.hidden = source.required || !source.value;
        this.wrapper.classList.toggle('has-clear', !this.clear.hidden);
        input.placeholder = source.options[0]?.value === '' ? source.options[0].textContent.trim() : 'Pesquisar…';
        for (const attribute of ['form', 'aria-invalid', 'aria-describedby', 'aria-label', 'aria-labelledby']) {
            if (source.hasAttribute(attribute)) { input.setAttribute(attribute, source.getAttribute(attribute)); } else { input.removeAttribute(attribute); }
        }
        if (!this.opened) {
            input.value = source.value ? (source.selectedOptions[0]?.textContent.trim() ?? '') : '';
        }
        if (source.validity.valid) { input.setCustomValidity(''); }
        if (input.disabled) { this.close(); }
    }

    sourceOptions(pinnedOnly = false) {
        return [...this.source.options]
            .filter((option) => (!this.source.required || option.value !== '') && (!pinnedOnly || option.hasAttribute('data-autocomplete-pinned') || option.selected || option.value === ''))
            .map((option) => ({ value: option.value, label: option.textContent.trim(), disabled: option.disabled || option.parentElement.disabled, keywords: option.dataset.keywords }));
    }

    open(selectText = true) {
        if (this.opened || this.input.disabled) { return; }
        active?.close();
        active = this;
        this.opened = true;
        this.animationVersion++;
        this.popup.removeAttribute('data-closing');
        this.popup.hidden = false;
        if (this.popup.showPopover && !this.popup.matches(':popover-open')) { this.popup.showPopover(); }
        this.input.setAttribute('aria-expanded', 'true');
        this.wrapper.setAttribute('data-open', '');
        this.search('');
        this.position();
        this.highlight(this.activeIndex);
        if (selectText) { this.input.select(); }
    }

    close() {
        if (!this.opened) { return; }
        this.opened = false;
        if (active === this) { active = null; }
        this.requestVersion++;
        clearTimeout(this.debounce);
        this.controller?.abort();
        this.loading = false;
        this.input.setAttribute('aria-expanded', 'false');
        this.input.removeAttribute('aria-activedescendant');
        this.wrapper.removeAttribute('data-open');
        this.sync();
        const version = ++this.animationVersion;
        this.popup.setAttribute('data-closing', '');
        Promise.allSettled(this.popup.getAnimations().map((animation) => animation.finished)).then(() => {
            if (version !== this.animationVersion) { return; }
            if (this.popup.hidePopover && this.popup.matches(':popover-open')) { this.popup.hidePopover(); }
            this.popup.hidden = true;
            this.popup.removeAttribute('data-closing');
        });
    }

    search(query) {
        this.query = query;
        this.popup.scrollTop = 0;
        this.activeIndex = -1;
        this.requestVersion++;
        this.controller?.abort();
        clearTimeout(this.debounce);
        this.loading = false;
        this.nextPage = null;
        this.failed = false;
        this.options = filterAutocompleteOptions(this.sourceOptions(Boolean(this.source.dataset.autocompleteUrl)), query);
        this.render();
        if (this.source.dataset.autocompleteUrl) {
            this.message.textContent = 'Buscando opções…';
            this.message.hidden = false;
            this.live.textContent = 'Buscando opções…';
            this.debounce = setTimeout(() => this.load(1), query ? 250 : 0);
        }
    }

    async load(page) {
        if (!page || this.loading || !this.opened) { return; }
        const version = ++this.requestVersion;
        this.controller?.abort();
        this.controller = new AbortController();
        this.loading = true;
        this.failed = false;
        this.more.disabled = true;
        this.list.setAttribute('aria-busy', 'true');
        try {
            const url = new URL(this.source.dataset.autocompleteUrl, window.location.origin);
            url.searchParams.set('q', this.query);
            url.searchParams.set('page', page);
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: this.controller.signal });
            if (!response.ok) { throw new Error('Busca indisponível'); }
            const result = await response.json();
            if (!this.opened || version !== this.requestVersion) { return; }
            const options = page === 1 ? filterAutocompleteOptions(this.sourceOptions(true), this.query) : this.options;
            this.options = [...new Map([...options, ...result.options].map((option) => [String(option.value), option])).values()];
            this.nextPage = result.next_page;
            this.render();
        } catch (error) {
            if (error.name !== 'AbortError' && version === this.requestVersion) {
                this.failed = true;
                this.message.hidden = false;
                this.message.textContent = 'Não foi possível buscar. Tente novamente.';
                this.live.textContent = this.message.textContent;
                this.nextPage = page;
                this.more.hidden = false;
                this.more.textContent = 'Tentar novamente';
            }
        } finally {
            if (version === this.requestVersion) {
                this.loading = false;
                this.more.disabled = false;
                this.list.removeAttribute('aria-busy');
                this.position();
            }
        }
    }

    render() {
        this.list.replaceChildren(...this.options.map((option, index) => {
            const node = element('div', 'autocomplete-option', {
                id: `${this.list.id}-${index}`, role: 'option', 'aria-selected': String(String(option.value) === this.source.value), 'data-option-index': index,
            });
            node.textContent = option.label;
            return node;
        }));
        this.message.textContent = 'Nenhum resultado encontrado';
        this.message.hidden = this.options.length !== 0;
        this.more.hidden = !this.nextPage;
        this.more.textContent = 'Carregar mais opções';
        this.live.textContent = this.options.length ? `${this.options.length} opções disponíveis${this.nextPage ? '. Role para carregar mais.' : '.'}` : this.message.textContent;
        const selected = this.options.findIndex((option) => String(option.value) === this.source.value);
        this.highlight(this.activeIndex >= 0 ? Math.min(this.activeIndex, this.options.length - 1) : (selected >= 0 ? selected : (this.query && this.options.length ? 0 : -1)), false);
        this.position();
    }

    highlight(index, scroll = true) {
        this.activeIndex = index;
        [...this.list.children].forEach((node, position) => node.toggleAttribute('data-active', position === index));
        const node = this.list.children[index];
        if (!node) { this.input.removeAttribute('aria-activedescendant'); return; }
        this.input.setAttribute('aria-activedescendant', node.id);
        if (scroll) {
            const top = node.offsetTop;
            if (top < this.popup.scrollTop) { this.popup.scrollTop = top; }
            if (top + node.offsetHeight > this.popup.scrollTop + this.popup.clientHeight) { this.popup.scrollTop = top + node.offsetHeight - this.popup.clientHeight; }
        }
    }

    choose(option) {
        if (!option || this.input.disabled) { return; }
        let selected = [...this.source.options].find((item) => item.value === String(option.value));
        if (!selected) {
            selected = new Option(option.label, option.value);
            this.source.add(selected);
        }
        this.source.value = selected.value;
        this.source.dispatchEvent(new Event('input', { bubbles: true }));
        this.source.dispatchEvent(new Event('change', { bubbles: true }));
        this.close();
        this.sync();
    }

    keydown(event) {
        if (event.isComposing) { return; }
        if (['ArrowDown', 'ArrowUp'].includes(event.key)) {
            event.preventDefault();
            if (!this.opened) { this.open(false); }
            const direction = event.key === 'ArrowDown' ? 1 : -1;
            if (direction > 0 && this.activeIndex === this.options.length - 1 && this.nextPage) { this.load(this.nextPage); }
            this.highlight(nextAutocompleteIndex(this.activeIndex, direction, this.options.length));
        } else if (event.key === 'Enter') {
            event.preventDefault();
            if (!this.opened) { this.open(false); } else { this.choose(this.options[this.activeIndex]); }
        } else if (event.key === 'Escape' && this.opened) {
            event.preventDefault();
            event.stopPropagation();
            this.close();
        } else if (event.key === 'Tab') {
            this.close();
        } else if (event.key === 'Backspace' && !this.input.value && !this.source.required) {
            this.choose({ value: '', label: '' });
        }
    }

    position() {
        if (!this.opened) { return; }
        if (!this.source.isConnected || this.input.getClientRects().length === 0) { this.close(); return; }
        const bounds = this.input.getBoundingClientRect();
        const viewport = window.visualViewport;
        const viewportTop = viewport?.offsetTop ?? 0;
        const viewportHeight = viewport?.height ?? window.innerHeight;
        const viewportLeft = viewport?.offsetLeft ?? 0;
        const viewportWidth = viewport?.width ?? window.innerWidth;
        if (bounds.bottom < viewportTop || bounds.top > viewportTop + viewportHeight) { this.close(); return; }
        const below = viewportTop + viewportHeight - bounds.bottom - 8;
        const above = bounds.top - viewportTop - 8;
        const upward = below < 180 && above > below;
        const height = Math.max(40, Math.min(256, upward ? above : below));
        this.popup.style.width = `${Math.min(bounds.width, viewportWidth - 16)}px`;
        this.popup.style.maxHeight = `${height}px`;
        this.popup.style.left = `${Math.max(viewportLeft + 8, Math.min(bounds.left, viewportLeft + viewportWidth - this.popup.offsetWidth - 8))}px`;
        this.popup.style.top = `${upward ? bounds.top - this.popup.offsetHeight - 4 : bounds.bottom + 4}px`;
    }

    destroy() {
        this.controller?.abort();
        clearTimeout(this.debounce);
        this.popup.remove();
        if (active === this) { active = null; }
    }
}

export function syncAutocomplete(source) {
    instances.get(source)?.sync();
}

export function focusFormControl(control, options) {
    (instances.get(control)?.input ?? control)?.focus(options);
}

export function initializeAutocompletes(root = document) {
    const sources = [...root.querySelectorAll('select:not([multiple])')];
    if (root.matches?.('select:not([multiple])')) { sources.unshift(root); }
    sources.forEach((source) => {
        if (!instances.has(source)) { instances.set(source, new Autocomplete(source)); }
    });
    if (observer) { return; }
    observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach((node) => { if (node.nodeType === 1) { initializeAutocompletes(node); } });
            }
            const source = mutation.target.closest?.('select');
            if (source) { syncAutocomplete(source); }
            if (mutation.target.matches?.('fieldset')) {
                mutation.target.querySelectorAll('select').forEach(syncAutocomplete);
            }
        });
        instances.forEach((instance, source) => {
            if (!source.isConnected) { instance.destroy(); instances.delete(source); }
        });
    });
    observer.observe(document.body, { subtree: true, childList: true, attributes: true, attributeFilter: ['disabled', 'required', 'form', 'aria-invalid', 'aria-describedby', 'selected'] });
    document.addEventListener('pointerdown', (event) => {
        if (active && !active.wrapper.contains(event.target) && !active.popup.contains(event.target)) { active.close(); }
    });
    document.addEventListener('reset', (event) => setTimeout(() => {
        instances.forEach((instance, source) => { if (source.form === event.target) { instance.close(); instance.sync(); } });
    }, 0));
    document.addEventListener('dialog:before-close', () => active?.close(), true);
    document.addEventListener('scroll', (event) => { if (active && event.target !== active.popup) { active.position(); } }, true);
    window.addEventListener('resize', () => active?.position());
    window.visualViewport?.addEventListener('resize', () => active?.position());
    window.visualViewport?.addEventListener('scroll', () => active?.position());
}
