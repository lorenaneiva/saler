import assert from 'node:assert/strict';
import { test } from 'node:test';
import { formatProductPrice, parseProductPrice, productMargin } from '../../resources/js/product-pricing.js';

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
