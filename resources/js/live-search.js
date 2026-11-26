const searchDelay = 350;

function searchUrl(form) {
    const url = new URL(form.action);
    new FormData(form).forEach((value, key) => {
        if (typeof value === 'string' && value.trim() !== '') {
            url.searchParams.set(key, value.trim());
        }
    });

    return url;
}

export function replaceLiveRegions(page) {
    const replacements = [];
    document.querySelectorAll('[data-live-region][id]').forEach((region) => {
        const replacement = page.getElementById(region.id);
        if (replacement) {
            const imported = document.importNode(replacement, true);
            region.replaceWith(imported);
            replacements.push(imported);
        }
    });

    return replacements;
}

export async function fetchLivePage(url, options = {}) {
    const response = await fetch(url, { ...options, headers: { Accept: 'text/html', ...options.headers } });
    if (!response.ok) {
        return null;
    }

    return { url: response.url, page: new DOMParser().parseFromString(await response.text(), 'text/html') };
}

export function initializeLiveSearch() {
    const forms = document.querySelectorAll('form[data-live-search]');
    let pendingRequest;

    async function refreshRegions(form) {
        const url = searchUrl(form);
        pendingRequest?.abort();
        const request = new AbortController();
        pendingRequest = request;
        form.setAttribute('aria-busy', 'true');

        try {
            const result = await fetchLivePage(url, { signal: request.signal });
            if (!result) {
                window.location.assign(url);
                return;
            }

            replaceLiveRegions(result.page);
            window.history.replaceState(window.history.state, '', url);
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.assign(url);
            }
        } finally {
            if (pendingRequest === request) {
                form.removeAttribute('aria-busy');
            }
        }
    }

    forms.forEach((form) => {
        const input = form.querySelector('input[type="search"]');
        let searchedValue = input.value.trim();
        let timer;

        function scheduleSearch(delay = searchDelay) {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => {
                const value = input.value.trim();
                if (value === searchedValue) {
                    return;
                }
                searchedValue = value;
                refreshRegions(form);
            }, delay);
        }

        input.addEventListener('input', () => scheduleSearch());
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            scheduleSearch(0);
        });
        form.addEventListener('click', (event) => {
            if (!event.target.closest('a.clear-filters')) {
                return;
            }
            event.preventDefault();
            input.value = '';
            input.focus();
            scheduleSearch(0);
        });
    });
}
