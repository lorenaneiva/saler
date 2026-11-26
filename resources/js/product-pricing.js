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

/** Normalizes a plain decimal typed in a number input ("12.5") to two decimals ("12.50"); null when invalid. */
export function normalizePlainDecimal(value) {
    const text = String(value ?? '').trim();
    if (!/^\d+(?:\.\d{1,2})?$/.test(text)) {
        return null;
    }
    const [whole, fraction = ''] = text.split('.');
    return `${BigInt(whole)}.${fraction.padEnd(2, '0')}`;
}

function toUnits(value, decimals) {
    const [whole, fraction = ''] = value.split('.');
    return BigInt(whole) * 10n ** BigInt(decimals) + BigInt(fraction.padEnd(decimals, '0').slice(0, decimals));
}

/** Integer division rounded half away from zero, the BigDecimal HALF_UP used by the server. */
function roundedDivision(numerator, denominator) {
    const negative = (numerator < 0n) !== (denominator < 0n);
    const absoluteNumerator = numerator < 0n ? -numerator : numerator;
    const absoluteDenominator = denominator < 0n ? -denominator : denominator;
    const quotient = absoluteNumerator / absoluteDenominator + (absoluteNumerator % absoluteDenominator * 2n >= absoluteDenominator ? 1n : 0n);
    return negative ? -quotient : quotient;
}

function formatHundredths(value) {
    const sign = value < 0n ? '-' : '';
    const absolute = value < 0n ? -value : value;
    return `${sign}${(absolute / 100n).toLocaleString('pt-BR')},${String(absolute % 100n).padStart(2, '0')}`;
}

export const formatCents = (cents) => (cents < 0n ? `-R$ ${formatHundredths(-cents)}` : `R$ ${formatHundredths(cents)}`);

/**
 * Commercial summary of a product. Cost comes from a purchase being typed (quantity, unit cost and item discount)
 * or, without one, from the registered average cost. All inputs are normalized decimal strings or null.
 * Margin is profit over sale price (not markup); nothing is rounded before the final display.
 */
export function commercialSummary({ salePrice = null, quantity = null, unitCost = null, discount = null, averageCost = null }) {
    const purchase = quantity !== null || unitCost !== null;
    const summary = { cost: null, price: null, profit: null, margin: null, stock: null, isLoss: false, discountExceeds: false };
    let costNumerator = null;
    let costDenominator = 1n;

    if (purchase) {
        const units = /^\d+$/.test(String(quantity ?? '')) ? BigInt(quantity) : 0n;
        if (units > 0n) {
            summary.stock = `${units.toLocaleString('pt-BR')} un.`;
        }
        if (units > 0n && unitCost !== null) {
            const totalCents = units * toUnits(unitCost, 2) - toUnits(discount ?? '0', 2);
            if (totalCents < 0n) {
                summary.discountExceeds = true;
            } else {
                costNumerator = totalCents;
                costDenominator = units;
            }
        }
    } else if (averageCost !== null) {
        costNumerator = toUnits(averageCost, 4);
        costDenominator = 100n;
    }

    if (costNumerator !== null) {
        summary.cost = formatCents(roundedDivision(costNumerator, costDenominator));
    }
    if (salePrice === null) {
        return summary;
    }

    const priceCents = toUnits(salePrice, 2);
    summary.price = formatCents(priceCents);
    if (costNumerator === null) {
        return summary;
    }

    const profitNumerator = priceCents * costDenominator - costNumerator;
    summary.profit = formatCents(roundedDivision(profitNumerator, costDenominator));
    summary.isLoss = profitNumerator < 0n;
    if (priceCents > 0n) {
        summary.margin = `${formatHundredths(roundedDivision(profitNumerator * 10000n, priceCents * costDenominator))}%`;
    }

    return summary;
}
