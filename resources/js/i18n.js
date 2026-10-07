// the page sends the words its scripts show, already in the page's language, in a json data block.
// a missing word falls back to the english text, which is also the lookup key
let strings;

export function t(text, values = {}) {
    if (strings === undefined) {
        try {
            strings = JSON.parse(document.getElementById('i18n-strings')?.textContent || '{}');
        } catch {
            strings = {};
        }
    }
    let out = strings[text] ?? text;
    for (const [key, value] of Object.entries(values)) out = out.replaceAll(`:${key}`, String(value));
    return out;
}
