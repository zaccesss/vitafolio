<x-help-page slug="jobs">
    <h2>{{ __('Finding jobs') }}</h2>
    <p>{{ __('The Jobs page lists internships, placement years, spring weeks, graduate roles, apprenticeships and part-time jobs in the UK for the current recruitment cycle. They are gathered every night from job boards and from employers\' own careers sites.') }}</p>
    <ul>
        <li>{{ __('The tabs at the top filter by kind of role and show how many jobs each holds.') }}</li>
        <li>{!! __('Search by <strong>job title or employer</strong>, pick a <strong>field</strong> such as software, engineering, law or healthcare and filter by <strong>location</strong>.') !!}</li>
        <li>{{ __('A role posted for several cities appears once, with its cities listed together.') }}</li>
        <li>{!! __('<strong>View and apply</strong> opens the advert on the employer\'s site or the job board, where you apply. Vitafolio counts how often each job is opened, never who opened it.') !!}</li>
    </ul>

    <h2>{{ __('Saving and tracking applications') }}</h2>
    <p>{!! __('Signed in, press <strong>Save</strong> on any job to add it to <strong>My applications</strong> in your account menu. The saved copy keeps the title, employer, link and closing date, even after the listing closes.') !!}</p>
    <p>{{ __('Each application has a status: saved, applied, assessment, interview, offer, rejected or withdrawn. Tabs show how many sit at each stage. Open Update on an application to change its status, its dates or your notes. Moving it to Applied records today\'s date if you have not entered one.') }}</p>
    <p>{{ __('Roles found anywhere else can be added by hand with the form at the top of My applications. Only you can see your applications. They are included when you download your data and deleted with your account.') }}</p>
</x-help-page>
