<x-help-page slug="checking-a-cv">
    <h2>{{ __('What it does') }}</h2>
    <p>{!! __('<strong>Check a CV</strong> reads a CV the way an applicant tracking system does, then scores how well the system can read it, out of 100. It covers the headings, contact details, skills, education, experience, keywords and a readable layout. It also lists what it found, what it could not find and what to fix.') !!}</p>
    <p>{{ __('The score is guidance. Most employers use these systems to rank and search applications rather than reject them automatically, so a person usually still reads your CV.') }}</p>

    <h2>{{ __('Checking a CV') }}</h2>
    <ol>
        <li>{!! __('Open <strong>Check a CV</strong> from your account menu.') !!}</li>
        <li>{{ __('Choose one of your Vitafolio CVs, or upload a PDF or Word (.docx) file of up to 5 MB. Choosing a file selects it automatically.') }}</li>
        <li>{{ __('Optionally paste a job advert to see which of its keywords your CV has and which it is missing.') }}</li>
        <li>{{ __('Read the report. The line under the score names the CV that was checked.') }}</li>
    </ol>
    <p>{{ __('Uploaded files are read once to produce the report and are never stored. The report is kept only in your signed-in session so you can download it as JSON or Markdown.') }}</p>

    <h2>{{ __('Checking against a job') }}</h2>
    <p>{!! __('On the Jobs page, <strong>Check my CV against this job</strong> opens Check a CV with the advert filled in. Job boards share only the start of each advert, so for a full check open the advert, copy all of it and paste it over the text.') !!}</p>
</x-help-page>
