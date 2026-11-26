const remembered = new Map();

function keyFor(table, measure) {
    const widthBand = Math.round(window.innerWidth / 40);
    return `table-${measure}:${widthBand}:${window.location.pathname}:${table.closest('[data-live-table]')?.id ?? ''}`;
}

function recall(key) {
    if (remembered.has(key)) {
        return remembered.get(key);
    }
    try {
        return Number(window.sessionStorage.getItem(key)) || null;
    } catch {
        return null;
    }
}

function remember(key, value) {
    remembered.set(key, value);
    try {
        window.sessionStorage.setItem(key, String(value));
    } catch {
        // Storage may be unavailable; the in-memory value still covers this visit.
    }
}

function median(values) {
    const sorted = [...values].sort((first, second) => first - second);
    return sorted[Math.floor(sorted.length / 2)];
}

/**
 * Sizes the blank filler rows of paginated tables so a short page is exactly as tall as the last full page seen
 * (or, before any full page was seen, as tall as its typical row times the page size).
 */
export function syncTableFillers(root = document) {
    root.querySelectorAll('.data-table').forEach((table) => {
        const fillers = [...table.querySelectorAll('tbody.table-fillers > tr')];
        const rows = [...table.querySelectorAll('tbody:not(.table-fillers) > tr')];
        const heights = rows.map((row) => row.getBoundingClientRect().height);
        const contentHeight = heights.reduce((total, height) => total + height, 0);
        const realRowHeights = rows.flatMap((row, index) => (row.classList.contains('empty-row') ? [] : [heights[index]]));
        const rowKey = keyFor(table, 'row-height');
        const pageKey = keyFor(table, 'page-height');

        if (realRowHeights.length) {
            remember(rowKey, median(realRowHeights));
        }
        if (!table.querySelector('tbody.table-fillers')) {
            return;
        }
        if (fillers.length === 0) {
            remember(pageKey, contentHeight);
            return;
        }

        const rowHeight = recall(rowKey) ?? 41;
        const pageHeight = recall(pageKey) ?? contentHeight + fillers.length * rowHeight;
        table.style.setProperty('--table-row-height', `${Math.max(pageHeight - contentHeight, 0) / fillers.length}px`);
    });
}

export function initializeTableFillers() {
    syncTableFillers();
    document.addEventListener('live-regions:replaced', () => syncTableFillers());

    let resizeTimer;
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => syncTableFillers(), 150);
    });
}
