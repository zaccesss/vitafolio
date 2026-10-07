<x-help-page slug="analytics">
    <h2>{{ __('What is counted') }}</h2>
    <ul>
        <li>{!! __('<strong>Views</strong> of each CV and of your profile.') !!}</li>
        <li>{!! __('<strong>PDF downloads</strong> and <strong>file opens</strong>.') !!}</li>
        <li>{!! __('<strong>QR code scans</strong>, counted separately from links so you can tell when a printed CV is working.') !!}</li>
        <li>{!! __('<strong>Where visitors came from</strong>, such as LinkedIn or a search engine, when their browser says.') !!}</li>
    </ul>

    <h2>{{ __('How it is counted') }}</h2>
    <p>{{ __('Each visitor counts once a day per CV, using a code that changes daily. Your own visits are never counted. Nothing identifies a visitor. No cookies are used for counting.') }}</p>

    <h2>{{ __('Reading the charts') }}</h2>
    <p>{!! __('Open <strong>Analytics</strong> from the account menu. Choose the last 7, 30 or 90 days. Every chart has a table with the same numbers underneath it, so the figures never depend on reading a colour or a shape.') !!}</p>
</x-help-page>
