/** Writes a {@link commercialSummary} result into a `[data-commercial-summary]` block. */
export function renderCommercialSummary(block, summary, { costLabel } = {}) {
    const write = (key, value) => {
        const target = block.querySelector(`[data-summary="${key}"]`);
        if (!target) {
            return;
        }
        const empty = target.dataset.empty ?? '—';
        target.textContent = value ?? empty;
        target.classList.toggle('text-unknown', value === null && key === 'cost');
        target.classList.toggle('is-loss', summary.isLoss && (key === 'profit' || key === 'margin'));
    };

    ['cost', 'price', 'profit', 'margin', 'stock'].forEach((key) => write(key, summary[key]));
    if (costLabel) {
        block.querySelector('[data-summary-label="cost"]').textContent = costLabel;
    }
    block.querySelector('[data-summary-alert="loss"]').hidden = !summary.isLoss;
    block.querySelector('[data-summary-alert="discount"]').hidden = !summary.discountExceeds;
}
