<x-prose-page :title="__('About Vitafolio')" :intro="__('A free place to build, store and share every version of your CV.')">
    <h2>{{ __('Why it exists') }}</h2>
    <p>{{ __('Most people need more than one CV: one for software roles, another for research, a short one for a careers fair. Many CV builders charge for a second version. Job sites keep your CV inside their own walls. Vitafolio keeps all your versions together, lets you choose exactly who can see each one and gives each its own link.') }}</p>

    <h2>{{ __('What you can do') }}</h2>
    <ul>
        <li>{{ __('Keep several CVs, each public, unlisted or private.') }}</li>
        <li>{{ __('Build a CV here or upload one you already have as a PDF or Word file. You can also write it in LaTeX and compile it in your browser.') }}</li>
        <li>{{ __('Show projects with images and short videos as proof of your work.') }}</li>
        <li>{{ __('Pick a layout, accent colour and font for each CV. Download it as a polished PDF.') }}</li>
        <li>{{ __('Share with a link or a QR code. People can message you without showing your email address.') }}</li>
        <li>{{ __('See how many people viewed each CV and where they came from.') }}</li>
    </ul>

    <h2>{{ __('Built to be accessible and private') }}</h2>
    <p>{{ __('Vitafolio follows the Web Content Accessibility Guidelines, works with a keyboard and screen readers and offers light and dark themes. The interface is available in seven languages.') }}
        {!! __('It uses no advertising and no tracking cookies. Read the :privacy and the :accessibility.', ['privacy' => '<a href="'.e(route('privacy')).'">'.e(__('privacy policy')).'</a>', 'accessibility' => '<a href="'.e(route('accessibility')).'">'.e(__('accessibility statement')).'</a>']) !!}</p>

    <h2 id="contact">{{ __('Contact') }}</h2>
    <p>{!! __('Questions, ideas or problems? The :contact has a form and the quickest route for each kind of question.', ['contact' => '<a href="'.e(route('contact.show')).'">'.e(__('contact page')).'</a>']) !!}</p>
</x-prose-page>
