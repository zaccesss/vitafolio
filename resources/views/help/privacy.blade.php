<x-help-page slug="privacy">
    <h2>{{ __('Who can see a CV') }}</h2>
    <div class="not-prose overflow-x-auto">
        <table class="w-full text-start text-sm">
            <caption class="sr-only">{{ __('What each visibility setting does') }}</caption>
            <thead class="border-b border-line text-muted">
                <tr><th scope="col" class="py-2 pe-4">{{ __('Setting') }}</th><th scope="col" class="py-2 pe-4">{{ __('In the directory') }}</th><th scope="col" class="py-2 pe-4">{{ __('Opens by link') }}</th><th scope="col" class="py-2">{{ __('In search engines') }}</th></tr>
            </thead>
            <tbody>
                <tr class="border-b border-line"><th scope="row" class="py-2 pe-4">{{ __('Public') }}</th><td class="py-2 pe-4">{{ __('Yes') }}</td><td class="py-2 pe-4">{{ __('Yes') }}</td><td class="py-2">{{ __('Yes') }}</td></tr>
                <tr class="border-b border-line"><th scope="row" class="py-2 pe-4">{{ __('Unlisted') }}</th><td class="py-2 pe-4">{{ __('No') }}</td><td class="py-2 pe-4">{{ __('Yes') }}</td><td class="py-2">{{ __('No') }}</td></tr>
                <tr><th scope="row" class="py-2 pe-4">{{ __('Private') }}</th><td class="py-2 pe-4">{{ __('No') }}</td><td class="py-2 pe-4">{{ __('Only you') }}</td><td class="py-2">{{ __('No') }}</td></tr>
            </tbody>
        </table>
    </div>
    <p>{!! __('Set visibility for each CV under <strong>Look and privacy</strong>. Your profile has its own setting under <strong>Settings</strong>, then <strong>Public profile</strong>. A private profile also takes your CVs out of the directory and out of search engines.') !!}</p>

    <h2>{{ __('Your email address') }}</h2>
    <p>{!! __('Your email address only appears on a CV if you tick <strong>Show my email address</strong> for that CV. Visitors can still message you without it.') !!}</p>

    <h2>{{ __('What visitors leave behind') }}</h2>
    <p>{{ __('A visit is counted once a day using a code that changes every day, so nobody can follow a visitor from one day to the next. Your own visits are never counted. There are no advertising or tracking cookies.') }}</p>

    <h2>{{ __('Your data') }}</h2>
    <p>{!! __('Download everything stored about you or delete your account and every file with it. Both are under <strong>Settings</strong>, then <strong>Your data</strong>. Read the full :policy for the details.', ['policy' => '<a href="'.e(route('privacy')).'">'.e(__('privacy policy')).'</a>']) !!}</p>
</x-help-page>
