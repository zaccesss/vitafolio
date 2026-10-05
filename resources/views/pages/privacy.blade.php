<x-prose-page title="Privacy policy" intro="What Vitafolio stores about you, why and how to see or delete it." updated="4 October 2026">
    <h2>Who is responsible</h2>
    <p>Vitafolio is run by {{ config('vitafolio.owner.name') }}@if (config('vitafolio.contact_email')), who you can contact at <a href="mailto:{{ config('vitafolio.contact_email') }}">{{ config('vitafolio.contact_email') }}</a>@endif. Under UK data protection law they are the controller of the personal data described here.</p>

    <h2>What is stored and why</h2>
    <ul>
        <li><strong>Your account:</strong> name, email address and a securely hashed password, so you can sign in. Two-factor settings are stored encrypted if you turn them on.</li>
        <li><strong>Your profile and CVs:</strong> everything you choose to add, such as your headline, photo, links, CV content, projects and uploaded files. This is the service you asked for.</li>
        <li><strong>View statistics:</strong> each visit to a CV is counted once a day using a one-way code made from the date, the visitor's network address and browser. The address itself is never stored. The code cannot be turned back into it. The website a visitor came from is kept to show you where views come from.</li>
        <li><strong>Reports and messages:</strong> if someone reports a CV, a one-way code is stored instead of their address. Messages sent through a CV are passed on by email and not kept on the site.</li>
        <li><strong>Security records:</strong> failed sign-in attempts are counted for a short time to stop password guessing.</li>
    </ul>
    <p>The legal basis is the contract to provide the service you signed up for, plus legitimate interests in keeping the site secure and free of abuse.</p>

    <h2>Who else handles data</h2>
    <ul>
        <li><strong>Hosting and database:</strong> the site and its database run with cloud providers in Europe and the United States, under their data processing terms.</li>
        <li><strong>Email:</strong> verification, password reset and contact emails are sent through Resend.</li>
        <li><strong>Images and video:</strong> project images and videos you upload are stored and served by Cloudinary.</li>
        <li><strong>Security and analytics:</strong> Cloudflare provides spam protection on forms and anonymous visitor counts. Neither sets advertising or tracking cookies.</li>
        <li><strong>The LaTeX editor</strong> compiles entirely in your browser. Your LaTeX only reaches the server when you save it.</li>
    </ul>
    <p>Your data is never sold. There is no advertising.</p>

    <h2>Who can see your information</h2>
    <p>You decide. Your profile and each CV can be public, unlisted or private. Your email address is hidden unless you choose to show it on a CV. Moderators can see reported CVs to deal with abuse.</p>

    <h2>How long it is kept</h2>
    <p>Your account and content are kept until you delete them. Deleting your account removes your profile, every CV, file, picture, project and statistic immediately. Copies in database backups are overwritten within 30 days.</p>

    <h2>Your rights</h2>
    <p>You can see and download everything stored about you from <a href="{{ route('account') }}">your account</a>, correct it at any time and delete your account yourself. You can also ask for any of this by email, object to processing or complain to the Information Commissioner's Office at <a href="https://ico.org.uk/make-a-complaint/">ico.org.uk</a>.</p>

    <h2>Cookies</h2>
    <p>Vitafolio only uses cookies needed to make the site work. The <a href="{{ route('cookies') }}">cookie policy</a> lists each one.</p>
</x-prose-page>
