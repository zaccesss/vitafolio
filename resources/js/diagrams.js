// mermaid blocks in the documentation and changelog pages are drawn as charts, the same way GitHub shows
// them. Each chart becomes an image, so the page's strict content security policy needs no exception,
// and it is redrawn when the theme changes. The source stays underneath as text for screen readers and
// for anyone who wants it. A block that fails to draw is simply left as code.
// mermaid sizes its drawing to the page with a percentage width, which an image cannot use, so the
// drawing gets the real width and height from its own viewBox
function sized(svg) {
    const box = svg.match(/viewBox="[\d.-]+ [\d.-]+ ([\d.]+) ([\d.]+)"/);
    if (!box) return svg;
    return svg.replace(/<svg([^>]*?)\swidth="[^"]*"/, '<svg$1').replace('<svg', `<svg width="${box[1]}" height="${box[2]}"`);
}

import { t } from './i18n.js';

export async function renderDiagrams(blocks) {
    const { default: mermaid } = await import('mermaid');
    const draw = async () => {
        const dark = document.documentElement.dataset.theme === 'dark';
        mermaid.initialize({ startOnLoad: false, securityLevel: 'strict', theme: dark ? 'dark' : 'neutral', fontFamily: 'system-ui, sans-serif' });
        for (const [index, block] of blocks.entries()) {
            try {
                const { svg } = await mermaid.render(`diagram-${index}-${Date.now()}`, block.source);
                block.image.src = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(sized(svg))}`;
                block.figure.hidden = false;
                block.pre.hidden = true;
            } catch {
                block.figure.hidden = true;
                block.pre.hidden = false;
            }
        }
    };
    await draw();
    new MutationObserver(draw).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
}

export function prepareDiagrams() {
    return [...document.querySelectorAll('pre > code.language-mermaid')].map((code, index) => {
        const pre = code.parentElement;
        const figure = document.createElement('figure');
        figure.className = 'diagram';
        figure.hidden = true;
        const image = document.createElement('img');
        image.alt = t('Diagram :number. Its text version follows.', { number: index + 1 });
        const details = document.createElement('details');
        const summary = document.createElement('summary');
        summary.textContent = t('Show the diagram as text');
        const source = document.createElement('pre');
        source.textContent = code.textContent;
        details.append(summary, source);
        figure.append(image, details);
        pre.after(figure);
        return { pre, figure, image, source: code.textContent };
    });
}
