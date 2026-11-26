import assert from 'node:assert/strict';
import { test } from 'node:test';
import { commercialSummary, formatProductPrice, normalizePlainDecimal, parseProductPrice, productMargin } from '../../resources/js/product-pricing.js';

test('converts Brazilian prices to exact decimal values for saving', () => {
    for (const [input, expected] of [
        ['1.234,56', '1234.56'], ['R$ 12,50', '12.50'], ['0,01', '0.01'],
        ['12,5', '12.50'], ['1234', '1234.00'], ['0', '0.00'], ['00012,50', '12.50'],
        ['9.999.999.999,99', '9999999999.99'],
    ]) {
        assert.equal(parseProductPrice(input), expected);
    }
});

test('rejects ambiguous separators, excess decimals and invalid prices without silently rounding', () => {
    for (const input of ['', ' ', '12.50', '1,234.56', '12,345', '-10,00', '1e3', 'abc', '1.23,50']) {
        assert.equal(parseProductPrice(input), null, input);
    }
});

test('formats prices with Brazilian grouping and two decimal places', () => {
    assert.equal(formatProductPrice('1234.56'), '1.234,56');
    assert.equal(formatProductPrice('0.00'), '0,00');
    assert.equal(formatProductPrice('9999999999.99'), '9.999.999.999,99');
    assert.equal(formatProductPrice(''), '');
});

test('calculates the existing margin on sale price using four-decimal cost and half-up rounding', () => {
    assert.equal(productMargin('12.00', '7.5000'), '37,50%');
    assert.equal(productMargin('3.00', '2.0000'), '33,33%');
    assert.equal(productMargin('2.00', '1.9999'), '0,01%');
    assert.equal(productMargin('2.00', '2.0001'), '-0,01%');
    assert.equal(productMargin('5.00', '7.5000'), '-50,00%');
    assert.equal(productMargin('12.00', '0.0000'), '100,00%');
    assert.equal(productMargin('0.00', '7.5000'), '—');
    assert.equal(productMargin(null, '7.5000'), '—');
});

test('normalizes plain decimals typed in number inputs', () => {
    assert.equal(normalizePlainDecimal('12.5'), '12.50');
    assert.equal(normalizePlainDecimal('400'), '400.00');
    assert.equal(normalizePlainDecimal(''), null);
    assert.equal(normalizePlainDecimal('1,5'), null);
    assert.equal(normalizePlainDecimal('-1'), null);
});

test('summarizes a typed purchase with net unit cost, gross profit and margin on sale price', () => {
    assert.deepEqual(commercialSummary({ salePrice: '400.00', quantity: '5', unitCost: '250.00', discount: null }), {
        cost: 'R$ 250,00', price: 'R$ 400,00', profit: 'R$ 150,00', margin: '37,50%', stock: '5 un.', isLoss: false, discountExceeds: false,
    });
    const discounted = commercialSummary({ salePrice: '800.00', quantity: '2', unitCost: '550.00', discount: '100.00' });
    assert.equal(discounted.cost, 'R$ 500,00');
    assert.equal(discounted.profit, 'R$ 300,00');
    assert.equal(discounted.margin, '37,50%');
});

test('keeps full precision until display when the discount does not split evenly', () => {
    const summary = commercialSummary({ salePrice: '10.00', quantity: '3', unitCost: '10.00', discount: '10.00' });
    assert.equal(summary.cost, 'R$ 6,67');
    assert.equal(summary.profit, 'R$ 3,33');
    assert.equal(summary.margin, '33,33%');
});

test('flags losses and discounts above the gross value', () => {
    const loss = commercialSummary({ salePrice: '200.00', quantity: '1', unitCost: '250.00', discount: null });
    assert.equal(loss.profit, '-R$ 50,00');
    assert.equal(loss.margin, '-25,00%');
    assert.equal(loss.isLoss, true);
    const excessive = commercialSummary({ salePrice: '10.00', quantity: '1', unitCost: '5.00', discount: '6.00' });
    assert.equal(excessive.discountExceeds, true);
    assert.equal(excessive.cost, null);
    assert.equal(excessive.margin, null);
});

test('uses the registered average cost and leaves profit and margin unknown without a cost', () => {
    const known = commercialSummary({ salePrice: '12.00', averageCost: '7.5000' });
    assert.equal(known.cost, 'R$ 7,50');
    assert.equal(known.margin, '37,50%');
    assert.equal(commercialSummary({ salePrice: '3.00', averageCost: '2.0000' }).margin, '33,33%');
    const unknown = commercialSummary({ salePrice: '12.00', averageCost: null });
    assert.equal(unknown.cost, null);
    assert.equal(unknown.profit, null);
    assert.equal(unknown.margin, null);
    assert.equal(commercialSummary({ salePrice: '0.00', averageCost: '1.0000' }).margin, null);
});
