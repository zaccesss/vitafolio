<?php

// made-up people for the demo recordings, loaded into the throwaway sqlite database by record.sh.
// alex morgan's cv is normally created on screen by the build clip; DEMO_ALEX_CV=1 creates the
// finished version here instead, so the other clips and the screenshots can be recorded on their own

use App\Models\User;
use App\Support\Tags;

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
