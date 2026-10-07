@php
    $faqs = [
        // answers are escaped here, so only the one link below is markup
        [__('Is it free?'), e(__('Yes. There are no paid plans and no adverts.'))],
        [__('How many CVs can I keep?'), e(__('Up to ten, each with its own address and privacy setting.'))],
        [__('Who can see a private CV?'), e(__('Only you and the site’s moderators.'))],
        [__('Do employers need an account to read my CV?'), e(__('No. Public and unlisted CVs open for anyone with the link.'))],
        [__('Can I use the CV I already have?'), e(__('Yes. Upload a PDF or Word file, import a JSON Resume file or write one in the LaTeX editor. A CV can have both built content and a file.'))],
        [__('Can I change my handle?'), e(__('Yes, once every 30 days. Your old handle redirects to the new one for 30 days.'))],
        [__('What happens if I delete my account?'), e(__('Your profile, CVs, files, photos and connected sign-ins are deleted straight away, including copies held by our file storage provider.'))],
        [__('Can I take my CV elsewhere?'), e(__('Yes. Export any CV as JSON Resume, an open format other tools can read. You can also download all your data at once from your settings.'))],
        [__('How do I see where my views come from?'), e(__('Open Analytics from the account menu. To name a link you share yourself, add ?utm_source= and a name to the end of it, for example ?utm_source=newsletter.'))],
        [__('I found a problem. Where do I report it?'), __('Use the :contact. Report a CV that breaks the rules with the :report button on that CV.', ['contact' => '<a href="'.e(route('contact.show')).'">'.e(__('contact page')).'</a>', 'report' => '<strong>'.e(__('Report')).'</strong>'])],
    ];
@endphp
<x-help-page slug="faq">
    <div class="not-prose grid gap-3">
        @foreach ($faqs as [$question, $answer])
            <details class="faq card px-5 py-4" @if($loop->first) open @endif>
                <summary class="cursor-pointer text-lg font-semibold">{{ $question }}</summary>
                <p class="mt-3 text-ink">{!! $answer !!}</p>
            </details>
        @endforeach
    </div>
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($faqs)->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => html_entity_decode(strip_tags($f[1]), ENT_QUOTES | ENT_HTML5)]])->all(),
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
</x-help-page>
