<?php

// made-up people for the demo recordings, loaded into the throwaway sqlite database by record.sh.
// alex morgan's cv is normally created on screen by the build clip; DEMO_ALEX_CV=1 creates the
// finished version here instead, so the other clips and the screenshots can be recorded on their own

use App\Models\Application;
use App\Models\JobListing;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Tags;
use Illuminate\Support\Str;

$availability = ['placement', 'internship', 'graduate', 'freelance'];
$people = [
    ['Alex Morgan', 'alexmorgan', 'Electronic engineering student', 'Northbridge University', 'Northbridge, UK', 'Embedded systems, PCB design and test automation.', null, null, 0],
    ['Priya Shah', 'priyashah', 'Data science student', 'Kingsford University', 'Leeds, UK', 'Turning messy datasets into clear dashboards.', 'Python, pandas, SQL, Power BI, scikit-learn', 'Data analyst roles', 214],
    ['Tom Okafor', 'tomokafor', 'Computer science student', 'Northbridge University', 'Manchester, UK', 'Backend services and developer tooling.', 'Java, Spring Boot, PostgreSQL, Docker, AWS', 'Backend placements', 167],
    ['Sofia Rossi', 'sofiarossi', 'Mechanical engineering student', 'Easton Institute', 'Bristol, UK', 'CAD, simulation and rapid prototyping.', 'SolidWorks, ANSYS, MATLAB, 3D printing', 'Design engineering', 98],
    ['Daniel Kim', 'danielkim', 'Software engineering graduate', 'Riverside University', 'London, UK', 'Accessible web apps with a focus on performance.', 'TypeScript, React, Next.js, Tailwind CSS, Playwright', 'Frontend roles', 341],
    ['Amira Haddad', 'amirahaddad', 'Cyber security student', 'Kingsford University', 'Birmingham, UK', 'Network defence, CTFs and secure code review.', 'Python, Wireshark, Linux, Burp Suite, Bash', 'Security internships', 122],
];

// only ever used in the throwaway local database; the capture script signs in with the same value
$password = getenv('DEMO_PASSWORD') ?: 'demo-only-password';

foreach ($people as $i => [$name, $handle, $headline, $university, $location, $bio, $skills, $title, $views]) {
    $user = User::updateOrCreate(['email' => "{$handle}@example.com"], [
        'name' => $name,
        'password' => $password,
        'handle' => $handle,
        'headline' => $headline,
        'bio' => $bio,
        'location' => $location,
        'university' => $university,
        'availability' => $availability[$i % count($availability)],
        'links' => "https://github.com/{$handle}\nhttps://example.com/{$handle}",
        'profile_visibility' => 'public',
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    if ($title === null) {
        continue;
    }

    $cv = $user->cvs()->updateOrCreate(['slug' => "{$handle}-cv"], [
        'title' => $title,
        'headline' => $headline,
        'visibility' => 'public',
        'profile' => "{$bio} Looking for a year in industry where I can ship real work.",
        'experience' => "Student intern, Example Labs (2025-06 to 2025-09)\n- Built internal tools used by 40 engineers\n- Cut test time by 30% with automation",
        'education' => "BEng/BSc, {$university} (2024-09 to present)",
    ]);
    Tags::sync($cv, Tags::parse($skills));
    $cv->forceFill(['view_count' => $views])->save();
}

if (getenv('DEMO_ALEX_CV')) {
    $alex = User::where('handle', 'alexmorgan')->firstOrFail();
    $cv = $alex->cvs()->updateOrCreate(['slug' => 'alex-morgan-embedded-software-roles'], [
        'title' => 'Embedded software roles',
        'headline' => 'Electronic engineering student, embedded systems',
        'key_language' => 'C',
        'visibility' => 'public',
        'theme' => 'modern',
        'accent' => 'teal',
        'font' => 'sans',
        'profile' => 'Second-year electronic engineering student who enjoys bringing up new boards, writing firmware and automating hardware tests.',
        'experience' => "Hardware test intern, Example Robotics (2025-06 to 2025-09)\n- Wrote Python rigs that test 200 motor boards a day\n- Found a power sequencing bug before production\n\nSociety lead, Northbridge Robotics Society (2024-10 to present)\n- Run weekly STM32 workshops for 30 members",
        'education' => 'BEng Electronic Engineering, Northbridge University (2024-09 to present)',
    ]);
    Tags::sync($cv, Tags::parse('C, Python, KiCad, STM32, FreeRTOS, Git, Linux'));
}

// made-up employers and listings for the jobs and applications clips. every address is on example.com,
// so nothing on screen points at a real company or advert
$alex = User::where('handle', 'alexmorgan')->firstOrFail();
$jobs = [
    ['internship', 'hardware', 'Embedded Firmware Intern', 'Kestrel Semiconductors', 'Cambridge', 'Summer 2027 internship writing C firmware for low-power radio chips. You will use STM32 boards, FreeRTOS, Git and Python test rigs alongside our silicon team.'],
    ['placement', 'hardware', 'Electronics Placement Year 2027', 'Northwind Robotics', 'Bristol', 'A 12 month placement designing and testing motor control boards. Skills: KiCad, C, oscilloscopes, Python and Linux.'],
    ['placement', 'software', 'Software Engineering Placement', 'Harbour Analytics', 'Leeds', 'Year in industry building data pipelines in Python and SQL with code review and continuous integration.'],
    ['internship', 'data', 'Data Science Summer Intern', 'Brightline Energy', 'London', 'Ten week internship forecasting energy demand with Python, pandas and scikit-learn.'],
    ['graduate', 'engineering', 'Graduate Electrical Engineer', 'Meridian Rail', 'Birmingham; Derby; York', 'Graduate scheme for September 2027 across signalling and power projects.'],
    ['insight', 'finance', 'Technology Spring Week', 'Fairhaven Bank', 'London', 'A week in April 2027 meeting engineering teams across the bank.'],
    ['apprenticeship', 'software', 'Degree Apprentice Software Developer', 'Calder Systems', 'Manchester', 'A four year degree apprenticeship building web services in Java and TypeScript.'],
    ['part-time', 'retail', 'Student Ambassador', 'Northbridge University', 'Northbridge', 'Part-time term-time role leading campus tours and open days.'],
    ['internship', 'law', 'Summer Vacation Scheme', 'Ashworth Legal', 'London', 'Two week vacation scheme in summer 2027 across commercial and technology law.'],
    ['graduate', 'health', 'Graduate Clinical Scientist', 'Westmoor Health Trust', 'Sheffield', 'Training post in medical physics and clinical engineering.'],
    ['placement', 'business', 'Consulting Placement Year', 'Larkspur Consulting', 'Edinburgh', 'Placement year supporting digital transformation projects for public sector clients.'],
    ['internship', 'creative', 'Marketing and Content Intern', 'Fernway Studios', 'Glasgow', 'Summer internship producing video and social content.'],
];
foreach ($jobs as $i => [$kind, $sector, $title, $company, $location, $description]) {
    $slug = Str::slug($company.' '.$title);
    JobListing::updateOrCreate(['source' => 'employer', 'external_id' => $slug], [
        'board' => 'Careers site', 'kind' => $kind, 'sector' => $sector, 'title' => $title, 'company' => $company,
        'location' => $location, 'salary_min' => $kind === 'part-time' ? null : 21000 + 1500 * $i, 'salary_max' => $kind === 'part-time' ? null : 25000 + 1500 * $i,
        'description' => $description, 'url' => "https://example.com/careers/{$slug}",
        'posted_at' => now()->subHours(6 * $i), 'closes_at' => now()->addDays(20 + 4 * $i), 'last_seen_at' => now(),
    ]);
}

// two roles already on alex's tracker, so the applications page has a story before the clip adds more
$tracked = JobListing::where('title', 'Software Engineering Placement')->first();
Application::updateOrCreate(['user_id' => $alex->id, 'job_listing_id' => $tracked->id], [
    'title' => $tracked->title, 'company' => $tracked->company, 'location' => $tracked->location, 'url' => $tracked->url,
    'kind' => $tracked->kind, 'status' => 'interview', 'applied_on' => now()->subDays(12)->toDateString(), 'deadline' => null,
    'notes' => 'Technical interview booked for next Tuesday.',
]);
Application::updateOrCreate(['user_id' => $alex->id, 'title' => 'Hardware Test Placement', 'company' => 'Example Avionics'], [
    'location' => 'Southampton', 'url' => 'https://example.com/careers/hardware-test-placement', 'status' => 'applied',
    'applied_on' => now()->subDays(5)->toDateString(), 'deadline' => now()->addDays(9)->toDateString(), 'notes' => 'Found at the university careers fair.',
]);

// google and github already connected, microsoft left to connect, as a real account would look
$alex->socialAccounts()->updateOrCreate(['provider' => 'google'], ['provider_id' => 'demo-google-1', 'email' => 'alexmorgan@example.com']);
$alex->socialAccounts()->updateOrCreate(['provider' => 'github'], ['provider_id' => 'demo-github-1', 'email' => 'alexmorgan@example.com']);

// an earlier ticket with a reply from the support team, so the conversation view has both sides
$ticket = SupportTicket::firstOrCreate(['user_id' => $alex->id, 'subject' => 'Adding a second email address'], [
    'name' => $alex->name, 'email' => $alex->email, 'category' => 'account', 'status' => 'waiting',
    'token_hash' => hash('sha256', Str::random(40)), 'last_activity_at' => now()->subDay(),
]);
if ($ticket->wasRecentlyCreated) {
    $ticket->messages()->create(['user_id' => $alex->id, 'from_staff' => false, 'body' => 'Can I add my university email as well as my personal one?']);
    $ticket->messages()->create(['user_id' => null, 'from_staff' => true, 'body' => "Each account has one email address, but you can change it under **Settings, Name and email**.\n\nYou can also connect Google or Microsoft to sign in with your university account."]);
}
