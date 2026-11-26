const decimalFormatter = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export function parseProductPrice(value) {
    const text = value.trim().replace(/^R\$\s*/, '');
    if (!/^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,2})?$/.test(text)) {
        return null;
    }

    const [whole, fraction = ''] = text.replaceAll('.', '').split(',');
    return `${BigInt(whole)}.${fraction.padEnd(2, '0')}`;
}

export function formatProductPrice(value) {
    return value === '' || value === null ? '' : decimalFormatter.format(Number(value));
}

export function productMargin(price, cost) {
    if (price === null) {
        return '—';
    }

    const scaled = (value) => {
        const [whole, fraction = ''] = value.split('.');
        return BigInt(whole) * 10000n + BigInt(fraction.padEnd(4, '0'));
    };
    const salePrice = scaled(price);
    if (salePrice === 0n) {
        return '—';
    }

    const difference = salePrice - scaled(cost);
    const numerator = (difference < 0n ? -difference : difference) * 10000n;
    const rounded = numerator / salePrice + (numerator % salePrice * 2n >= salePrice ? 1n : 0n);
    const sign = difference < 0n && rounded !== 0n ? '-' : '';
    return `${sign}${(rounded / 100n).toLocaleString('pt-BR')},${String(rounded % 100n).padStart(2, '0')}%`;
}
