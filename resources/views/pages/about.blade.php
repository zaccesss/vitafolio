<x-prose-page title="About Vitafolio" intro="A free place to build, store and share every version of your CV.">
    <h2>Why it exists</h2>
    <p>
        Most people need more than one CV: one for software roles, another for research, a short one for a careers fair.
        Many CV builders charge for a second version. Job sites keep your CV inside their own walls.
        Vitafolio keeps all your versions together, lets you choose exactly who can see each one and gives each its own link.
    </p>

    <h2>What you can do</h2>
    <ul>
        <li>Keep several CVs, each public, unlisted or private.</li>
        <li>Build a CV here or upload one you already have as a PDF or Word file. You can also write it in LaTeX and compile it in your browser.</li>
        <li>Show projects with images and short videos as proof of your work.</li>
        <li>Pick a layout, accent colour and font for each CV. Download it as a polished PDF.</li>
        <li>Share with a link or a QR code. People can message you without showing your email address.</li>
        <li>See how many people viewed each CV and where they came from.</li>
    </ul>

    <h2>Built to be accessible and private</h2>
    <p>
        Vitafolio follows the Web Content Accessibility Guidelines, works with a keyboard and screen readers and offers light and dark themes.
        It uses no advertising and no tracking cookies. Read the <a href="{{ route('privacy') }}">privacy policy</a> and the <a href="{{ route('accessibility') }}">accessibility statement</a>.
    </p>

    <h2 id="contact">Contact</h2>
    @if (config('vitafolio.contact_email'))
        <p>Questions, ideas or problems? Send a message and you will get a reply by email.</p>
        <div class="not-prose">
            <x-error-summary />
            <form method="POST" action="{{ route('contact') }}" class="card grid gap-4 p-6">
                @csrf
                <x-field name="sender_name" label="Your name" required autocomplete="name" maxlength="100" />
                <x-field name="sender_email" label="Your email" type="email" required autocomplete="email" maxlength="254" />
                <x-field name="message" label="Message" type="textarea" rows="6" required maxlength="3000" counter />
                {{-- hidden from people; only bots fill it in --}}
                <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                <x-turnstile />
                <div><button type="submit" class="btn btn-primary">Send message</button></div>
            </form>
        </div>
    @else
        <p>The contact form is not set up on this site yet.</p>
    @endif
</x-prose-page>
