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
        'checking-a-cv' => ['Checking a CV', 'How Check a CV reads a CV the way tracking systems do, what the score means and how to use a job advert.'],
        'jobs' => ['Jobs and applications', 'Finding internships, placements, graduate roles and apprenticeships, saving them and tracking each application.'],
        'support-tickets' => ['Support tickets', 'Opening a ticket, adding screenshots, following the conversation and what happens to tickets over time.'],
        'faq' => ['Frequently asked questions', 'Short answers to the questions people ask most.'],
    ];
}
