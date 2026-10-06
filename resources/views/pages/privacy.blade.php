@php
    $owner = config('vitafolio.owner.name');
    $email = config('vitafolio.contact_email');
    $sections = ['who' => 'Who is responsible', 'what' => 'What we collect', 'why' => 'Why we use it and our legal bases', 'visitors' => 'People who visit a CV', 'sharing' => 'Who we share it with', 'transfers' => 'Transfers outside the UK', 'keep' => 'How long we keep it', 'rights' => 'Your rights', 'security' => 'How we protect it', 'children' => 'Children', 'changes' => 'Changes to this policy', 'contact' => 'Contact and complaints'];
@endphp
<x-prose-page title="Privacy policy" intro="What {{ config('app.name') }} collects, why, who sees it and the rights you have over it." updated="6 October 2026">
    <nav aria-labelledby="privacy-contents" class="not-prose card p-5">
        <h2 id="privacy-contents" class="text-lg">Contents</h2>
        <ol class="mt-2 grid gap-1 text-sm sm:grid-cols-2">
            @foreach ($sections as $id => $label)
                <li><a href="#{{ $id }}">{{ $loop->iteration }}. {{ $label }}</a></li>
            @endforeach
        </ol>
    </nav>

    <h2 id="who">1. Who is responsible</h2>
    <p>{{ config('app.name') }} is run by {{ $owner }}, who is the data controller for the personal data described here under the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018.@if ($email) You can contact the controller at <a href="mailto:{{ $email }}">{{ $email }}</a> or through the <a href="{{ route('contact.show') }}">contact page</a>.@endif</p>

    <h2 id="what">2. What we collect</h2>
    <h3>Information you give us</h3>
    <ul>
        <li><strong>Account details:</strong> your name, email address and a password stored only as a secure one-way hash. If you turn on two-factor authentication, its secret and recovery codes are stored encrypted. Passkeys store only a public key; your fingerprint, face or PIN never leaves your device.</li>
        <li><strong>Profile:</strong> your handle, photo, headline, bio, pronouns, location, university, availability and links.</li>
        <li><strong>CVs:</strong> everything you add to a CV, such as its sections, skills, projects, images, videos, uploaded files and LaTeX source.</li>
        <li><strong>Messages and reports:</strong> what you write when you contact us, message a CV owner or report a CV.</li>
    </ul>
    <h3>Information from sign-in providers</h3>
    <p>If you sign in with Google, GitHub or Microsoft, we receive your name, email address, profile photo address and an identifier for your account with that provider. We never receive your password for that provider. We never post anything on your behalf.</p>
    <h3>Information collected automatically</h3>
    <ul>
        <li><strong>Sign-in records:</strong> a session cookie keeps you signed in. Failed sign-in attempts are counted for a short time against your email and network address to stop password guessing.</li>
        <li><strong>Visit counts:</strong> see <a href="#visitors">section 4</a>.</li>
        <li><strong>Error reports:</strong> if something breaks, a technical report of the error is recorded. These reports are set up to leave out personal details such as your email address.</li>
        <li><strong>Site analytics:</strong> where enabled, Cloudflare Web Analytics counts page visits without cookies and without identifying you.</li>
    </ul>

    <h2 id="why">3. Why we use it and our legal bases</h2>
    <div class="not-prose overflow-x-auto">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">Each purpose and its legal basis</caption>
            <thead class="border-b border-line text-muted">
                <tr><th scope="col" class="py-2 pr-4">Purpose</th><th scope="col" class="py-2">Legal basis</th></tr>
            </thead>
            <tbody>
                <tr class="border-b border-line"><td class="py-2 pr-4">Providing your account, profile and CVs to you and to the people you choose</td><td class="py-2">Contract: it is the service you signed up for</td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4">Sending account emails such as verification, password resets and messages from visitors</td><td class="py-2">Contract</td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4">Keeping the site secure, preventing abuse and moderating reported content</td><td class="py-2">Legitimate interests in a safe service for everyone</td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4">Counting visits to show you how your CVs are doing</td><td class="py-2">Legitimate interests, using counts that cannot identify anyone</td></tr>
                <tr><td class="py-2 pr-4">Keeping records the law requires</td><td class="py-2">Legal obligation</td></tr>
            </tbody>
        </table>
    </div>
    <p>We never sell your data, never show adverts and never use your data to build marketing profiles.</p>
    <h3>Automated decisions</h3>
    <p>We make no decisions about you by automated means. Two automated checks run without affecting your rights: Cloudflare Turnstile scores whether a form submission is likely to come from an automated script, and new passwords are checked against a list of known breached passwords using only a short hash prefix.</p>

    <h2 id="visitors">4. People who visit a CV</h2>
    <p>When someone opens a CV or profile, we count the visit once per day. To do that we make a one-way code from the date, the visitor's network address and their browser details. The network address itself is never stored. The code changes every day and cannot be turned back into the address. We also keep the website the visitor came from, when their browser shares it, so owners can see where their views come from. The owner's own visits are never counted.</p>
    <p>If a visitor reports a CV, we store a one-way code instead of their network address so repeat reports can be recognised without identifying anyone.</p>

    <h2 id="sharing">5. Who we share it with</h2>
    <p>Content you make public is visible to anyone. It may also be indexed by search engines unless you make it unlisted or private. Beyond that, we only share data with the service providers that run the site for us, under contracts that limit what they can do with it:</p>
    <div class="not-prose overflow-x-auto">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">Service providers and what they do</caption>
            <thead class="border-b border-line text-muted">
                <tr><th scope="col" class="py-2 pr-4">Provider</th><th scope="col" class="py-2">What they do</th></tr>
            </thead>
            <tbody>
                <tr class="border-b border-line"><td class="py-2 pr-4">Render</td><td class="py-2">Runs the website, in Frankfurt, Germany</td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4">TiDB Cloud (PingCAP)</td><td class="py-2">Hosts the database on AWS in Frankfurt, Germany</td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4">Cloudinary</td><td class="py-2">Stores CV files, project images and videos</td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4">Resend</td><td class="py-2">Delivers account emails and messages from visitors</td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4">Cloudflare</td><td class="py-2">Routes traffic to the site, protects forms from spam and counts visits without cookies</td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4">Sentry</td><td class="py-2">Records technical error reports</td></tr>
                <tr><td class="py-2 pr-4">Google, GitHub and Microsoft</td><td class="py-2">Confirm who you are, only if you choose to sign in with them</td></tr>
            </tbody>
        </table>
    </div>
    <p>We would only disclose data to anyone else if the law requires it or to protect someone's safety.</p>

    <h2 id="transfers">6. Transfers outside the UK</h2>
    <p>The website and database run in Germany, which the UK recognises as giving adequate protection. Some providers listed above are based in the United States. Where data reaches them, the transfer is protected by safeguards recognised under UK law, such as the UK International Data Transfer Addendum or the UK Extension to the EU to US Data Privacy Framework.</p>

    <h2 id="keep">7. How long we keep it</h2>
    <ul>
        <li><strong>Your account, profile and CVs:</strong> until you delete them or your account. Deleting your account removes your profile, CVs, files, photos and connected sign-ins straight away, including copies held by our file storage provider.</li>
        <li><strong>Database backups:</strong> deleted data can remain in encrypted backups for a short time until they are replaced, usually within a few days.</li>
        <li><strong>Visit counts:</strong> 13 months, then deleted automatically.</li>
        <li><strong>Sessions and sign-in records:</strong> until they expire, usually within hours.</li>
        <li><strong>Old handles:</strong> 30 days after you change your handle, so nobody else can take it and pose as you.</li>
        <li><strong>Reports:</strong> until they have been dealt with and for as long as needed to handle repeat abuse.</li>
        <li><strong>Error reports:</strong> as set by our error tracking provider, usually 30 to 90 days.</li>
    </ul>

    <h2 id="rights">8. Your rights</h2>
    <p>Under UK GDPR you have the right to:</p>
    <ul>
        <li><strong>access</strong> your data: download everything from <a href="{{ route('settings.data') }}">Settings, then Your data</a>;</li>
        <li><strong>correct</strong> it: edit your profile and CVs at any time;</li>
        <li><strong>erase</strong> it: delete a CV or your whole account from your settings;</li>
        <li><strong>take it elsewhere:</strong> export CVs as JSON Resume or your whole account as JSON;</li>
        <li><strong>object</strong> to or ask us to <strong>restrict</strong> how we use it, including anything based on legitimate interests.</li>
    </ul>
    <h3>Your right to object</h3>
    <p>You can object at any time to our use of your data that relies on legitimate interests, such as visit counting or abuse prevention. Contact us and we will stop unless we can show a compelling reason to continue. You can also stop visit counting on your own CVs at any time by making them private.</p>
    <p>To use a right that the settings do not cover, contact us. We will reply within one month.</p>

    <h2 id="security">9. How we protect it</h2>
    <p>Everything travels over encrypted connections. Passwords are hashed. Sessions and two-factor secrets are encrypted. Private files can only be opened after a permission check. We support passkeys and two-factor authentication, refuse passwords found in known breaches and limit repeated attempts. No system is perfectly secure, so please use a strong, unique password or a passkey. To report a security problem, see our <a href="{{ url('/.well-known/security.txt') }}">security contact</a>.</p>

    <h2 id="children">10. Children</h2>
    <p>{{ config('app.name') }} is for people aged 16 and over. If you believe a younger person has created an account, contact us and we will remove it.</p>

    <h2 id="changes">11. Changes to this policy</h2>
    <p>If we change how we use your data, we will update this page and its date. For significant changes we will tell account holders by email before they take effect.</p>

    <h2 id="contact">12. Contact and complaints</h2>
    <p>Questions about your data go to @if ($email)<a href="mailto:{{ $email }}">{{ $email }}</a> or @endif the <a href="{{ route('contact.show') }}">contact page</a>. If you are unhappy with how we handle your data, you can complain to the Information Commissioner's Office at <a href="https://ico.org.uk/make-a-complaint/" rel="noopener">ico.org.uk</a>. We would appreciate the chance to put things right first.</p>
</x-prose-page>
