@php
    $site = config('app.name');
    $owner = config('vitafolio.owner.name');
    $source = config('vitafolio.source_url');
@endphp
<x-prose-page english-only title="Copyright and licences" intro="Who owns what on {{ $site }} and the licences behind it." updated="7 October 2026">
    <h2 id="cvs">Your CVs belong to you</h2>
    <p>Everything you add to {{ $site }}, such as CVs, projects, photos and files, stays yours. You give us only the limited licence described in the <a href="{{ route('terms') }}#content">terms of use</a>, so we can store your content and show it to the people you choose. Reusing someone else's CV needs their permission, not ours.</p>

    <h2 id="site">The site and its code</h2>
    <p>&copy; {{ date('Y') }} {{ $owner }}. @if ($source)The code behind {{ $site }} is open source under the MIT Licence and is published on <a href="{{ $source }}" rel="noopener">GitHub</a>. You may use, copy and adapt it, provided the licence and copyright notice stay with it.@else The code behind {{ $site }} is open source under the MIT Licence.@endif</p>
    <p>The {{ $site }} name and logo identify this service. They are not covered by the code licence, so please do not use them for a copy you run yourself or in a way that suggests it is this site.</p>

    <h2 id="third-party">Third-party material</h2>
    <ul>
        <li><strong>Fonts:</strong> Inter, Source Serif 4, JetBrains Mono, Noto Naskh Arabic, Noto Nastaliq Urdu and Noto Sans SC, each under the SIL Open Font License 1.1.</li>
        <li><strong>LaTeX in the browser:</strong> BusyTeX and the TeX Live packages it bundles, each under its own free licence. Documents you compile are yours.</li>
        <li><strong>Libraries:</strong> Laravel, Vue, Alpine.js, Tailwind CSS and CodeMirror under the MIT Licence. PDFs are generated with Typst under the Apache License 2.0, with mPDF under the GNU GPL version 2 as a fallback. The DejaVu fonts in generated PDFs are under the Bitstream Vera and DejaVu licences.</li>
        <li><strong>Sign-in logos:</strong> the Google, GitHub and Microsoft names and logos belong to their owners and appear only on their sign-in buttons. Their use does not mean any of them endorse {{ $site }}.</li>
    </ul>

    <h2 id="report">Reporting a copyright problem</h2>
    <p>If a CV uses your work without permission, use the <strong>Report</strong> button on that CV. You can also tell us through the <a href="{{ route('contact.show') }}">contact page</a> with a link to it. We will look at it straight away and remove anything that infringes.</p>
</x-prose-page>
