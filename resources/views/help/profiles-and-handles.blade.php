<x-help-page slug="profiles-and-handles">
    <h2>{{ __('Your profile') }}</h2>
    <p>{!! __('Your profile page lists your public CVs alongside your photo, headline, bio and links. Find it by opening the account menu in the top corner and choosing <strong>View your public profile</strong>.') !!}</p>

    <h2>{{ __('Your handle') }}</h2>
    <p>{!! __('Your handle is your username. It makes your profile address, for example :example. A handle is made from your name when you sign up. You can change it under <strong>Settings</strong>, then <strong>Handle</strong>.', ['example' => '<code dir="ltr">/&#64;alex-morgan</code>']) !!}</p>
    <ul>
        <li>{{ __('Handles use 3 to 30 lowercase letters, numbers and single hyphens.') }}</li>
        <li>{{ __('You can change your handle once every 30 days.') }}</li>
        <li>{{ __('Your old handle keeps working for 30 days and sends people to the new one. Nobody else can take it during that time, so nobody can pose as you.') }}</li>
        <li>{!! __('A few words, such as <em>admin</em> and <em>support</em>, are reserved.') !!}</li>
    </ul>

    <h2>{{ __('Photo') }}</h2>
    <p>{!! __('Choose a photo under <strong>Settings</strong>, then <strong>Photo</strong>. Drag it inside the circle, zoom with the slider or use the arrow keys to frame it, then upload. Location and camera details are removed from every photo.') !!}</p>

    <h2>{{ __('Links') }}</h2>
    <p>{!! __('Add up to ten links, one per line. Well-known sites such as GitHub, LinkedIn, ORCID, Google Scholar, Mastodon and Bluesky are named for you. Only <code>http</code> and <code>https</code> addresses are kept.') !!}</p>
</x-help-page>
