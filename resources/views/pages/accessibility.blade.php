<x-prose-page title="Accessibility statement" intro="Vitafolio is built to be usable by as many people as possible." updated="4 October 2026">
    <h2>What to expect</h2>
    <ul>
        <li>Every page works with a keyboard alone, with a visible focus outline on every control.</li>
        <li>Text and controls meet the WCAG 2.2 AA contrast requirements in both light and dark themes. Colour is never the only way information is shown.</li>
        <li>Pages use proper headings, landmarks and labels, so screen readers announce them clearly. Form errors are listed at the top and linked to each field.</li>
        <li>Animation is decoration only and switches off when your device asks for reduced motion. Videos never play on their own.</li>
        <li>Text can be zoomed to 400% without losing content. Layouts adapt to small screens.</li>
        <li>CV layouts include a plain option that is easy for screen readers and applicant tracking systems to read.</li>
    </ul>

    <h2>Known limitations</h2>
    <ul>
        <li>Files people upload, such as PDFs, Word documents and videos, are only as accessible as their authors made them.</li>
        <li>The LaTeX code editor works with screen readers but can be awkward, so it offers a plain text box instead.</li>
        <li>PDFs compiled from LaTeX may not include tags for screen readers. The online CV page always has the same content in an accessible form.</li>
    </ul>

    <h2>Tell us about a problem</h2>
    <p>If something does not work for you, @if (config('vitafolio.contact_email'))email <a href="mailto:{{ config('vitafolio.contact_email') }}">{{ config('vitafolio.contact_email') }}</a> or @endif use the <a href="{{ route('about') }}#contact">contact form</a>. Accessibility problems are treated as bugs and fixed as a priority.</p>
</x-prose-page>
