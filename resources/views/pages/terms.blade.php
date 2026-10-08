@php
    $site = config('app.name');
    $owner = config('vitafolio.owner.name');
    $sections = ['about' => 'About these terms', 'eligibility' => 'Who can use it', 'account' => 'Your account', 'content' => 'Your content', 'rules' => 'What is not allowed', 'moderation' => 'Reports and moderation', 'service' => 'The service', 'jobs' => 'Jobs and Check a CV', 'liability' => 'Liability', 'ending' => 'Ending your use', 'changes' => 'Changes', 'law' => 'Law and disputes', 'contact' => 'Contact'];
@endphp
<x-prose-page english-only title="Terms of use" intro="The agreement between you and {{ $site }}, in plain English." updated="8 October 2026">
    <nav aria-labelledby="terms-contents" class="not-prose card p-5">
        <h2 id="terms-contents" class="text-lg">Contents</h2>
        <ol class="mt-2 grid gap-1 text-sm sm:grid-cols-2">
            @foreach ($sections as $id => $label)
                <li><a href="#{{ $id }}">{{ $loop->iteration }}. {{ $label }}</a></li>
            @endforeach
        </ol>
    </nav>

    <h2 id="about">1. About these terms</h2>
    <p>These terms apply to everyone who uses {{ $site }}, which is run by {{ $owner }} ("we" or "us"). By creating an account or using the site you agree to them. The <a href="{{ route('privacy') }}">privacy policy</a> explains how we handle personal data and forms part of this agreement.</p>

    <h2 id="eligibility">2. Who can use it</h2>
    <p>You must be at least 16 years old to create an account. Each person may have one account, which is for their own use and cannot be transferred or sold.</p>

    <h2 id="account">3. Your account</h2>
    <ul>
        <li>Give accurate details when you sign up and keep your email address up to date, as we use it to contact you about your account.</li>
        <li>Keep your password and any passkeys or recovery codes safe. You are responsible for what happens on your account.</li>
        <li>Tell us straight away if you think someone else has used your account.</li>
    </ul>

    <h2 id="content">4. Your content</h2>
    <p>Everything you add, such as CVs, projects, photos, files and messages, stays yours. To run the service, you give us a non-exclusive, royalty-free licence to store, copy, format and show your content to the people allowed by the visibility you choose. That includes making share images, PDFs and QR codes from it. The licence ends when you delete the content or your account, apart from short-lived backup copies.</p>
    <p>You are responsible for your content. Only add content that is accurate and that you have the right to share. Do not upload other people's personal information without their permission.</p>
    <p>Content you make public can be seen by anyone and may be copied or indexed by search engines and other sites outside our control. Choose unlisted or private for anything you do not want widely shared.</p>

    <h2 id="rules">5. What is not allowed</h2>
    <ul>
        <li>Pretending to be another person, a company or a university.</li>
        <li>Claiming qualifications or jobs you do not have.</li>
        <li>Content that is illegal, hateful, harassing, threatening, sexually explicit or discriminatory.</li>
        <li>Spam, advertising, scams or misleading links.</li>
        <li>Uploading malware or files designed to harm devices or people.</li>
        <li>Collecting other users' data, including by scraping or automated access beyond normal browsing.</li>
        <li>Trying to break, overload or get around the security of the site.</li>
        <li>Accessing accounts that are not yours.</li>
        <li>Infringing anyone's copyright, trade marks or other rights.</li>
    </ul>
    <p>Found a security problem? Report it privately through our <a href="{{ url('/.well-known/security.txt') }}">security contact</a>. Good-faith reports made that way are welcome and are not a breach of these terms.</p>

    <h2 id="moderation">6. Reports and moderation</h2>
    <p>Anyone can report a CV that breaks these rules. Moderators review reports and may hide content, remove a photo or suspend an account. Where we can, we will tell you what was done and why. If you think a decision was wrong, contact us and it will be looked at again.</p>

    <h2 id="service">7. The service</h2>
    <p>{{ $site }} is free. We work to keep it available, secure and correct, but cannot promise it will always be available or free of errors. We may change, add or remove features. We will give notice before removing anything significant. Keep your own copy of anything important; you can download all your data at any time.</p>

    <h2 id="jobs">8. Jobs and Check a CV</h2>
    <p>Job listings come from job boards and from employers' own careers sites. Vitafolio does not write, check or endorse them and is not part of any recruitment process. You apply on the employer's site or the board itself, under its own terms and privacy policy. A listing may close or change before it leaves the Jobs page.</p>
    <p>Check a CV gives guidance based on how common applicant tracking systems read CVs. Employers set their systems up differently, so the score does not predict whether an application will succeed.</p>

    <h2 id="liability">9. Liability</h2>
    <p>Because the service is free and provided as it is, we are not liable for indirect losses, lost opportunities or lost data, to the extent the law allows. Nothing in these terms limits liability for death or personal injury caused by negligence, for fraud or for anything else that cannot be limited under the law of England and Wales. If you are a consumer, these terms do not affect your statutory rights.</p>

    <h2 id="ending">10. Ending your use</h2>
    <p>You can delete your account at any time from <a href="{{ route('settings.data') }}">Settings, then Your data</a>. We may suspend or close an account that seriously or repeatedly breaks these terms. If we close the service entirely, we will give at least 30 days' notice so you can download your data.</p>

    <h2 id="changes">11. Changes</h2>
    <p>We may update these terms. The date at the top shows when they last changed. For significant changes we will tell account holders by email before they take effect. Continuing to use the site after that means you accept the new terms.</p>

    <h2 id="law">12. Law and disputes</h2>
    <p>These terms are governed by the law of England and Wales. Please contact us first so we can try to resolve any problem. If that does not work, the courts of England and Wales will decide, though if you live elsewhere in the UK you may use your local courts.</p>

    <h2 id="contact">13. Contact</h2>
    <p>Questions about these terms? Use the <a href="{{ route('contact.show') }}">contact page</a>.</p>
</x-prose-page>
