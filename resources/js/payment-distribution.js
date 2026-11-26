const previewDelay = 250;

function setUpPreview(container) {
    const form = container.closest('form');
    const input = form.elements.amount;
    let timer;
    let request;
    let previewedAmount = null;

    async function loadPreview() {
        const amount = input.value.trim();
        request?.abort();
        const current = new AbortController();
        request = current;
        container.setAttribute('aria-busy', 'true');

        const url = new URL(container.dataset.paymentDistribution, window.location.href);
        url.searchParams.set('amount', amount);

        try {
            const response = await fetch(url, { headers: { Accept: 'text/html' }, signal: current.signal, cache: 'no-store' });
            if (!response.ok) {
                throw new Error('preview');
            }
            container.innerHTML = await response.text();
            previewedAmount = amount;
        } catch (error) {
            if (error.name !== 'AbortError') {
                previewedAmount = null;
                container.innerHTML = '<p class="payment-distribution-message">Não foi possível calcular a distribuição agora. Tente novamente em instantes.</p>';
            }
        } finally {
            if (request === current) {
                container.removeAttribute('aria-busy');
            }
        }
    }

    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(loadPreview, previewDelay);
    });

    // The amount must be confirmed against the distribution the user is looking at.
    form.addEventListener('submit', (event) => {
        if (previewedAmount === input.value.trim() && !container.hasAttribute('aria-busy')) {
            return;
        }
        event.preventDefault();
        window.clearTimeout(timer);
        loadPreview().then(() => container.querySelector('table')?.closest('[tabindex]')?.focus());
    });

    if (input.value.trim() !== '') {
        loadPreview();
    }
}

export function initializePaymentDistributions() {
    document.querySelectorAll('[data-payment-distribution]').forEach(setUpPreview);
}
