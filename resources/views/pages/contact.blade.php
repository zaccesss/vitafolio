<x-layouts.app title="Contact" description="Get in touch about your account, report a problem or suggest an idea." turnstile>
    <div class="container-page py-10">
        <div class="max-w-2xl">
            <h1 class="text-4xl">Contact</h1>
            <p class="mt-3 text-lg text-muted">Questions, ideas or problems? Choose the quickest route below. You can also send a message and you will get a reply by email.</p>
        </div>

        <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <section aria-labelledby="message-title">
                <h2 id="message-title" class="text-2xl">Send a message</h2>
                @if (config('vitafolio.contact_email'))
                    <p class="mt-2 text-muted">Replies usually arrive within a few working days. Never include a password or a sign-in code.</p>
                    <x-error-summary />
                    <form method="POST" action="{{ route('contact') }}" class="card mt-5 grid gap-4 p-6">
                        @csrf
                        <x-field name="sender_name" label="Your name" required autocomplete="name" maxlength="100" />
                        <x-field name="sender_email" label="Your email" type="email" required autocomplete="email" maxlength="254" />
                        <x-field name="message" label="Message" type="textarea" rows="7" required maxlength="3000" counter />
                        {{-- hidden from people; only bots fill it in --}}
                        <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        <x-turnstile />
                        <div><button type="submit" class="btn btn-primary">Send message</button></div>
                    </form>
                @else
                    <p class="mt-2 text-muted">The contact form is not set up on this copy of the site yet.</p>
                @endif
            </section>

            <aside aria-labelledby="routes-title" class="grid content-start gap-4">
                <h2 id="routes-title" class="text-2xl">Other ways to get help</h2>
                @if (Route::has('help'))
                    <div class="card p-5">
                        <h3 class="text-lg">Help centre</h3>
                        <p class="mt-1 text-sm text-muted">Guides for building, sharing and protecting your CVs, plus answers to common questions.</p>
                        <a class="mt-3 inline-block font-semibold" href="{{ route('help') }}">Open the help centre</a>
                    </div>
                @endif
                @if (config('vitafolio.source_url'))
                    <div class="card p-5">
                        <h3 class="text-lg">Report a bug or suggest an idea</h3>
                        <p class="mt-1 text-sm text-muted">Bugs and ideas are tracked in the open, so others can follow and add to them.</p>
                        <a class="mt-3 inline-block font-semibold" href="{{ rtrim(config('vitafolio.source_url'), '/') }}/issues/new/choose" rel="noopener">Open an issue</a>
                    </div>
                @endif
                <div class="card p-5">
                    <h3 class="text-lg">Security problems</h3>
                    <p class="mt-1 text-sm text-muted">Please report these privately and never in public, so they can be fixed before anyone else learns of them.</p>
                    <a class="mt-3 inline-block font-semibold" href="{{ url('/.well-known/security.txt') }}">How to report one</a>
                </div>
                <div class="card p-5">
                    <h3 class="text-lg">A CV that breaks the rules</h3>
                    <p class="mt-1 text-sm text-muted">Use the <strong>Report</strong> button at the bottom of the CV. Reports are anonymous and go straight to the moderators.</p>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.app>
