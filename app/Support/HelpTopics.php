<?php

namespace App\Support;

/** the help centre's topics in reading order; each has a view at resources/views/help/{slug}.blade.php */
class HelpTopics
{
    public const ALL = [
        'getting-started' => ['Getting started', 'Create an account, confirm your email and find your first CV.'],
        'building-a-cv' => ['Building a CV', 'Sections, skills, projects, cover letters, themes and the order things appear in.'],
        'profiles-and-handles' => ['Profiles and handles', 'Your public profile, your @handle and how often it can change.'],
        'sharing' => ['Sharing and PDFs', 'Links, QR codes, PDFs, share previews, messages from visitors and endorsements.'],
        'files-and-latex' => ['Uploaded files and LaTeX', 'Uploading PDF and Word files and writing a CV in LaTeX.'],
        'privacy' => ['Privacy and visibility', 'Who can see each CV and profile. How search engines treat them.'],
        'account-security' => ['Account security', 'Passwords, two-factor authentication, passkeys and connected accounts.'],
        'analytics' => ['Analytics', 'How views are counted and what the charts show.'],
        'faq' => ['Frequently asked questions', 'Short answers to the questions people ask most.'],
    ];
}
