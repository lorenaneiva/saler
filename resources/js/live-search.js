const searchDelay = 350;
const searchTimers = new WeakMap();
let pendingRequest;

function sortedQuery(url) {
    const params = new URLSearchParams(url.search);
    params.sort();
    return params.toString();
}

function searchUrl(form) {
    const url = new URL(form.action);
    new FormData(form).forEach((value, key) => {
        if (typeof value === 'string' && value.trim() !== '') {
            url.searchParams.set(key, value.trim());
        }
    });

    return url;
}

function syncSearchForms(page) {
    document.querySelectorAll('form[data-live-search][id]').forEach((form) => {
        const source = page.getElementById(form.id);
        if (!source) {
            return;
        }
        Array.from(form.elements).forEach((field) => {
            const counterpart = field.name ? source.elements.namedItem(field.name) : null;
            if (counterpart && field !== document.activeElement && 'value' in counterpart) {
                field.value = counterpart.value;
                field.disabled = counterpart.disabled;
            }
        });
    });
}

export function replaceLiveRegions(page) {
    const replacements = [];
    document.querySelectorAll('[data-live-region][id]').forEach((region) => {
        const replacement = page.getElementById(region.id);
        if (replacement && region.isConnected) {
            const imported = document.importNode(replacement, true);
            region.replaceWith(imported);
            replacements.push(imported);
        }
    });
    syncSearchForms(page);
    document.dispatchEvent(new CustomEvent('live-regions:replaced', { detail: { regions: replacements } }));

    return replacements;
}

export async function fetchLivePage(url, options = {}) {
    const response = await fetch(url, { ...options, headers: { Accept: 'text/html', ...options.headers } });
    if (!response.ok) {
        return null;
    }

    return { url: response.url, page: new DOMParser().parseFromString(await response.text(), 'text/html') };
}

/** Loads another state of the current page and swaps its live regions, keeping scroll and focus in place. */
export async function navigateLiveTable(url, table) {
    pendingRequest?.abort();
    const request = new AbortController();
    pendingRequest = request;
    table?.setAttribute('aria-busy', 'true');

    try {
        const result = await fetchLivePage(url, { signal: request.signal });
        if (!result) {
            window.location.assign(url);
            return false;
        }
        replaceLiveRegions(result.page);
        window.history.replaceState(window.history.state, '', url);

        return true;
    } catch (error) {
        if (error.name !== 'AbortError') {
            window.location.assign(url);
        }

        return false;
    } finally {
        if (pendingRequest === request) {
            table?.removeAttribute('aria-busy');
        }
    }
}

function scheduleSearch(form, delay = searchDelay) {
    window.clearTimeout(searchTimers.get(form));
    searchTimers.set(form, window.setTimeout(() => {
        const url = searchUrl(form);
        if (sortedQuery(url) !== sortedQuery(new URL(window.location.href))) {
            navigateLiveTable(url, form.closest('[data-live-table]'));
        }
    }, delay));
}

function goToTypedPage(input) {
    if (!input?.isConnected || input.disabled) {
        return;
    }

    const form = input.form;
    const lastPage = Number(input.max) || 1;
    const currentPage = Number(input.dataset.currentPage) || 1;
    const typedPage = Number.parseInt(input.value, 10);

    if (!Number.isInteger(typedPage)) {
        input.value = currentPage;
        return;
    }

    const page = Math.min(Math.max(typedPage, 1), lastPage);
    input.value = page;
    if (page === currentPage) {
        return;
    }

    const url = searchUrl(form);
    const table = form.closest('[data-live-table]');
    if (table) {
        navigateLiveTable(url, table);
    } else {
        window.location.assign(url);
    }
}

function revealTable(table) {
    if (table && table.getBoundingClientRect().top < 0) {
        table.scrollIntoView({ block: 'start', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    }
}

export function initializeLiveSearch() {
    document.addEventListener('input', (event) => {
        const form = event.target.closest?.('form[data-live-search]');
        if (form && event.target.matches('input[type="search"]')) {
            scheduleSearch(form);
        }

        if (event.target.matches?.('.pagination-input') && !/^\d*$/.test(event.target.value)) {
            event.target.value = event.target.value.replace(/\D/g, '');
        }
    });

    document.addEventListener('change', (event) => {
        const form = event.target.closest?.('form[data-live-search]');
        if (form && event.target.matches('select')) {
            scheduleSearch(form, 0);
        }
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (form.matches('form[data-live-search]')) {
            event.preventDefault();
            scheduleSearch(form, 0);
        }

        if (form.matches('form[data-pagination-jump]')) {
            event.preventDefault();
            goToTypedPage(form.querySelector('.pagination-input'));
        }
    });

    document.addEventListener('focusout', (event) => {
        if (event.target.matches?.('.pagination-input')) {
            goToTypedPage(event.target);
        }
    });

    document.addEventListener('click', async (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const clear = event.target.closest('form[data-live-search] a.clear-filters');
        if (clear) {
            event.preventDefault();
            const form = clear.closest('form');
            await navigateLiveTable(new URL(clear.href), form.closest('[data-live-table]'));
            document.getElementById(form.id)?.querySelector('input[type="search"]')?.focus();
            return;
        }

        const sortLink = event.target.closest('[data-live-table] a[data-sort-link]');
        if (sortLink) {
            event.preventDefault();
            const table = sortLink.closest('[data-live-table]');
            const selector = `a[data-sort-link="${CSS.escape(sortLink.dataset.sortLink)}"]`;
            if (await navigateLiveTable(new URL(sortLink.href), table)) {
                document.getElementById(table.id)?.querySelector(selector)?.focus({ preventScroll: true });
            }
            return;
        }

        const step = event.target.closest('[data-live-table] a.pagination-step');
        if (step) {
            event.preventDefault();
            const table = step.closest('[data-live-table]');
            if (await navigateLiveTable(new URL(step.href), table)) {
                revealTable(document.getElementById(table.id));
            }
        }
    });
}
