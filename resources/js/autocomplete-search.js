export function normalizeAutocompleteText(value) {
    return String(value).normalize('NFD').replace(/\p{M}/gu, '').toLocaleLowerCase('pt-BR').trim();
}

export function filterAutocompleteOptions(options, query) {
    const term = normalizeAutocompleteText(query);
    return options.filter((option) => !option.disabled && normalizeAutocompleteText(`${option.label} ${option.keywords ?? ''}`).includes(term));
}

export function nextAutocompleteIndex(current, direction, count) {
    if (count === 0) {
        return -1;
    }
    if (current < 0) {
        return direction > 0 ? 0 : count - 1;
    }
    return Math.max(0, Math.min(count - 1, current + direction));
}
