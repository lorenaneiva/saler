import assert from 'node:assert/strict';
import { test } from 'node:test';
import { filterAutocompleteOptions, nextAutocompleteIndex, normalizeAutocompleteText } from '../../resources/js/autocomplete-search.js';

test('matches accented, decomposed and uppercase labels without changing the displayed text', () => {
    const options = [{ value: '1', label: 'Feijão São João' }, { value: '2', label: 'Açúcar' }, { value: '3', label: 'Cafe\u0301' }];
    assert.deepEqual(filterAutocompleteOptions(options, 'FEIJAO SAO'), [options[0]]);
    assert.deepEqual(filterAutocompleteOptions(options, 'ACUCAR'), [options[1]]);
    assert.deepEqual(filterAutocompleteOptions(options, 'CAFÉ'), [options[2]]);
    assert.equal(normalizeAutocompleteText('  CONCEIÇÃO  '), 'conceicao');
});

test('shows all enabled options for an empty search and no options for an unknown term', () => {
    const options = [{ value: '', label: 'Todos' }, { value: '1', label: 'Ativo' }, { value: '2', label: 'Indisponível', disabled: true }];
    assert.deepEqual(filterAutocompleteOptions(options, ''), options.slice(0, 2));
    assert.deepEqual(filterAutocompleteOptions(options, 'inexistente'), []);
});

test('searches literal characters and optional barcode keywords', () => {
    const options = [{ value: '1', label: '10% integral', keywords: '000123' }, { value: '2', label: '100 integral' }];
    assert.deepEqual(filterAutocompleteOptions(options, '10%'), [options[0]]);
    assert.deepEqual(filterAutocompleteOptions(options, '000123'), [options[0]]);
});

test('keyboard movement handles empty lists and stays within option boundaries', () => {
    assert.equal(nextAutocompleteIndex(-1, 1, 0), -1);
    assert.equal(nextAutocompleteIndex(-1, 1, 3), 0);
    assert.equal(nextAutocompleteIndex(-1, -1, 3), 2);
    assert.equal(nextAutocompleteIndex(0, -1, 3), 0);
    assert.equal(nextAutocompleteIndex(1, 1, 3), 2);
    assert.equal(nextAutocompleteIndex(2, 1, 3), 2);
});
