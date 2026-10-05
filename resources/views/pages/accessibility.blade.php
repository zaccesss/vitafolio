<x-prose-page title="Accessibility statement" intro="Vitafolio is built to be usable by as many people as possible." updated="4 October 2026">
    <h2>What to expect</h2>
    <ul>
        <li>Every page works with a keyboard alone, with a visible focus outline on every control.</li>
        <li>Text and controls meet the WCAG 2.2 AA contrast requirements in both light and dark themes. Colour is never the only way information is shown.</li>
        <li>Pages use proper headings, landmarks and labels, so screen readers announce them clearly. Form errors are listed at the top and linked to each field.</li>
        <li>Animation is decoration only and switches off when your device asks for reduced motion. Videos never play on their own.</li>
        <li>Text can be zoomed to 400% without losing content. Layouts adapt to small screens.</li>
        <li>CV layouts include a plain option that is easy for screen readers and applicant tracking systems to read.</li>
        <li>Signing in never needs a puzzle or a retyped code. Codes can be pasted. Passkeys or recovery codes work as alternatives to an authenticator app.</li>
    </ul>

    <h2 id="keyboard">Keyboard shortcuts</h2>
    <p>Every shortcut works the same in Chrome, Edge, Firefox and Safari. The only difference between systems is the modifier key.</p>
    <div class="not-prose overflow-x-auto">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">Keyboard shortcuts on Windows, Linux and Mac</caption>
            <thead class="border-b border-line text-muted">
                <tr><th scope="col" class="py-2 pr-4">Action</th><th scope="col" class="py-2 pr-4">Windows and Linux</th><th scope="col" class="py-2">Mac</th></tr>
            </thead>
            <tbody>
                <tr class="border-b border-line"><td class="py-2 pr-4">Move between links, buttons and fields</td><td class="py-2 pr-4"><kbd>Tab</kbd> and <kbd>Shift+Tab</kbd></td><td class="py-2"><kbd>Tab</kbd> and <kbd>Shift+Tab</kbd>. In Safari, links are skipped unless "Press Tab to highlight each item" is on in Safari's Advanced settings. <kbd>Option+Tab</kbd> also reaches them</td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4">Skip to the main content</td><td class="py-2 pr-4"><kbd>Tab</kbd> once on any page, then <kbd>Enter</kbd></td><td class="py-2">The same</td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4">Close a menu or dialog</td><td class="py-2 pr-4"><kbd>Esc</kbd></td><td class="py-2"><kbd>Esc</kbd></td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4">Open a question in the FAQ</td><td class="py-2 pr-4"><kbd>Enter</kbd> or <kbd>Space</kbd></td><td class="py-2"><kbd>Enter</kbd> or <kbd>Space</kbd></td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4">Compile in the LaTeX editor</td><td class="py-2 pr-4"><kbd>Ctrl+Enter</kbd></td><td class="py-2"><kbd>Cmd+Enter</kbd></td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4">Save in the LaTeX editor</td><td class="py-2 pr-4"><kbd>Ctrl+S</kbd> (with or without Caps Lock)</td><td class="py-2"><kbd>Cmd+S</kbd> (with or without Caps Lock)</td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4">Move the crop on a profile photo</td><td class="py-2 pr-4">Arrow keys</td><td class="py-2">Arrow keys</td></tr>
                <tr><td class="py-2 pr-4">Zoom a profile photo</td><td class="py-2 pr-4"><kbd>+</kbd> or <kbd>=</kbd> to zoom in, <kbd>-</kbd> to zoom out</td><td class="py-2">The same</td></tr>
            </tbody>
        </table>
    </div>

    <h2 id="browsers">Browsers and systems</h2>
    <p>The site is built for the current and previous versions of Chrome, Edge, Firefox and Safari on Windows, macOS, Linux, iOS, iPadOS and Android. That means Safari 16.4 or later, Chrome and Edge 111 or later and Firefox 128 or later. Older browsers can still read CVs, though some styling may look plainer. Windows contrast themes and system light or dark mode are both followed.</p>

    <h2>Known limitations</h2>
    <ul>
        <li>Files people upload, such as PDFs, Word documents and videos, are only as accessible as their authors made them.</li>
        <li>The LaTeX code editor works with screen readers but can be awkward, so it offers a plain text box instead.</li>
        <li>PDFs compiled from LaTeX may not include tags for screen readers. The online CV page always has the same content in an accessible form.</li>
    </ul>

    <h2>Tell us about a problem</h2>
    <p>If something does not work for you, @if (config('vitafolio.contact_email'))email <a href="mailto:{{ config('vitafolio.contact_email') }}">{{ config('vitafolio.contact_email') }}</a> or @endif use the <a href="{{ route('contact.show') }}">contact form</a>. Accessibility problems are treated as bugs and fixed as a priority.</p>
</x-prose-page>
