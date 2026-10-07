<x-help-page slug="building-a-cv">
    <h2>{{ __('Sections') }}</h2>
    <p>{{ __('The Content tab holds a headline for this CV, your main language, a profile summary, experience, education and skills. Leave any section empty and it is left out of the CV.') }}</p>
    <p>{!! __('A CV can use its own headline. That lets one CV say <em>Embedded software engineer</em> while another says <em>Research assistant</em>, without changing your profile.') !!}</p>

    <h2>{{ __('Skills') }}</h2>
    <p>{!! __('Type skills separated by commas. Common spellings are joined together, so <em>JS</em> and <em>JavaScript</em> become one skill. Skills on public CVs appear as filters in the directory, which helps people find you.') !!}</p>

    <h2>{{ __('Cover letter') }}</h2>
    <p>{!! __('Each CV can carry one cover letter, written on the <strong>Cover letter</strong> tab. Write plain paragraphs with a blank line between them. Add who it is for, such as the team and the role, if you like. The letter uses the CV\'s theme and privacy setting. Visitors switch between the CV and the letter at the top of the page and can download the letter as a PDF or Word document. Leave the letter empty and it is not shown at all.') !!}</p>

    <h2>{{ __('Projects') }}</h2>
    <p>{{ __('Add up to twelve projects to a CV, each with a title, a link and a description of what you did and what came of it. A project can show one image or one short video (up to 90 seconds). Describe the image or video for people who cannot see it; the description is read aloud by screen readers.') }}</p>

    <h2>{{ __('Order') }}</h2>
    <p>{!! __('Drag the sections into the order you want. You can also use the <strong>Move up</strong> and <strong>Move down</strong> buttons. The same order is used on the CV page and in its PDF.') !!}</p>

    <h2>{{ __('Themes, colours and fonts') }}</h2>
    <p>{{ __('Choose from four themes: Classic, Modern, Minimal and Plain. Plain is built to be read easily by screen readers and by the applicant tracking systems employers use. Every accent colour passes contrast checks in light and dark mode.') }}</p>

    <h2>{{ __('Document language') }}</h2>
    <p>{!! __('Choose the language of a CV under <strong>Look and privacy</strong>. It sets the fixed labels, such as the section headings, on the CV page and in its PDF and Word files, whatever language a visitor uses the site in. Arabic and Urdu CVs read right to left. What you write is never translated.') !!}</p>

    <h2>{{ __('Import and export') }}</h2>
    <p>{!! __('Import a <a href="https://jsonresume.org" rel="noopener">JSON Resume</a> file to fill a CV in one step. You can also export any CV in the same open format to use elsewhere.') !!}</p>
</x-help-page>
