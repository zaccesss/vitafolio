<x-prose-page english-only title="Cookie policy" intro="Vitafolio only uses cookies that are strictly necessary, so there is no cookie banner." updated="7 October 2026">
    <h2>Cookies Vitafolio sets</h2>
    <div class="not-prose overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead><tr class="border-b-2 border-line"><th scope="col" class="py-2 pr-4">Name</th><th scope="col" class="py-2 pr-4">Purpose</th><th scope="col" class="py-2">Lasts</th></tr></thead>
            <tbody>
                <tr class="border-b border-line"><td class="py-2 pr-4 font-mono">{{ \Illuminate\Support\Str::slug(config('app.name'), '_') }}_session</td><td class="py-2 pr-4">Keeps you signed in and remembers what you were doing. Encrypted.</td><td class="py-2">Until you close the browser or two hours of inactivity</td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4 font-mono">XSRF-TOKEN</td><td class="py-2 pr-4">Protects forms against cross-site request forgery.</td><td class="py-2">Same as the session</td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4 font-mono">remember_web_…</td><td class="py-2 pr-4">Keeps you signed in on this device. Only set if you tick "Keep me signed in".</td><td class="py-2">Up to 5 years or until you sign out</td></tr>
                <tr class="border-b border-line"><td class="py-2 pr-4 font-mono">locale</td><td class="py-2 pr-4">Remembers the language you picked from the language menu. Encrypted. Only set when you pick one.</td><td class="py-2">One year</td></tr>
            </tbody>
        </table>
    </div>

    <h2>Other storage</h2>
    <p>Your light, dark or system theme choice is saved in your browser's local storage, not in a cookie. It never leaves your device.</p>
    <p>The spam check on some forms is provided by Cloudflare Turnstile, which is classed as strictly necessary for security. Visitor statistics come from Cloudflare Web Analytics, which uses no cookies at all.</p>

    <h2>Managing cookies</h2>
    <p>You can block or delete cookies in your browser settings. Without the session cookie you will not be able to sign in.</p>
</x-prose-page>
