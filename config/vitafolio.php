<?php

/*
 * site settings that differ per deployment. nothing personal is hard-coded, so anyone can
 * run their own copy by filling in the environment variables listed in .env.example.
 */

return [

    'owner' => [
        'name' => env('SITE_OWNER_NAME', 'Vitafolio'),
        'url' => env('SITE_OWNER_URL'),
    ],

    // where messages from the public contact form are delivered
    'contact_email' => env('SITE_CONTACT_EMAIL'),

    'source_url' => env('SITE_SOURCE_URL'),

    // a public status page for the site, linked from the footer when set
    'status_url' => env('SITE_STATUS_URL'),

    'security_contact' => env('SITE_SECURITY_EMAIL', env('SITE_CONTACT_EMAIL')),

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret' => env('TURNSTILE_SECRET'),
    ],

    // cloudflare web analytics token; analytics stay off when it is empty
    'analytics_token' => env('CF_BEACON_TOKEN'),

    // shared secret for the nightly tidy-up call to /cron; the address returns 404 when it is empty
    'cron_token' => env('CRON_TOKEN'),

    // where browsers report content security policy breaks, for example an error tracker's
    // security endpoint; reporting stays off when it is empty
    'csp_report_uri' => env('CSP_REPORT_URI'),

    // indexnow key (any 8 to 128 letters, digits or dashes); search engine pings stay off when it is empty
    'indexnow_key' => env('INDEXNOW_KEY'),

    'per_page' => 12,

    'themes' => [
        'classic' => 'Classic',
        'modern' => 'Modern',
        'minimal' => 'Minimal',
        'plain' => 'Plain (applicant tracking friendly)',
    ],

    // each accent passes 4.5:1 against white for text and with white text on top of it
    'accents' => [
        'midnight' => ['label' => 'Midnight', 'hex' => '#14213d'],
        'brass' => ['label' => 'Brass', 'hex' => '#7a5b2e'],
        'purple' => ['label' => 'Purple', 'hex' => '#5c2d82'],
        'blue' => ['label' => 'Blue', 'hex' => '#1d4ed8'],
        'teal' => ['label' => 'Teal', 'hex' => '#0f766e'],
        'green' => ['label' => 'Green', 'hex' => '#15803d'],
        'red' => ['label' => 'Red', 'hex' => '#b91c1c'],
        'amber' => ['label' => 'Amber', 'hex' => '#92400e'],
        'slate' => ['label' => 'Slate', 'hex' => '#334155'],
    ],

    'fonts' => [
        'sans' => 'Inter (clean sans serif)',
        'serif' => 'Source Serif (classic serif)',
        'mono' => 'JetBrains Mono (technical)',
    ],

    /*
     * sign-in providers by route name. "trusts_email" is true only where the provider confirms the
     * address is verified, which is what allows joining an existing account with the same email.
     * microsoft does not guarantee that for every tenant, so it never joins an account by email.
     * "photo_hosts" are the only places a profile photo may be copied from.
     */
    'social' => [
        'google' => ['label' => 'Google', 'driver' => 'google', 'trusts_email' => true, 'photo_hosts' => ['googleusercontent.com']],
        'github' => ['label' => 'GitHub', 'driver' => 'github', 'trusts_email' => true, 'photo_hosts' => ['avatars.githubusercontent.com']],
        'microsoft' => ['label' => 'Microsoft', 'driver' => 'microsoft', 'trusts_email' => false, 'photo_hosts' => []],
        'linkedin' => ['label' => 'LinkedIn', 'driver' => 'linkedin-openid', 'trusts_email' => true, 'photo_hosts' => ['media.licdn.com']],
    ],

    'max_cvs_per_user' => 10,

    // upload limits. each file is capped on its own and an account's cv files and project media
    // together cannot pass the storage allowance, so one account can never fill the free plans
    'limits' => [
        'storage_mb' => (int) env('STORAGE_ALLOWANCE_MB', 100),
        'document_kb' => 5120,
        'image_kb' => 5120,
        'video_kb' => 25600,
        'video_seconds' => 90,
        'avatar_kb' => 4096,
    ],
    'max_projects_per_cv' => 12,

    // image and video hosting for project media; uploads stay off when these are empty
    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
    ],

    // where the in-browser latex engine files are served from
    'latex_assets_url' => env('LATEX_ASSETS_URL'),

    'profile_visibility' => [
        'public' => 'Public: your profile page is listed and searchable',
        'unlisted' => 'Unlisted: only people with the link can see it',
        'private' => 'Private: only you can see it. Your CVs leave the directory too',
    ],

    'visibility' => [
        'public' => 'Public: listed in the directory and searchable',
        'unlisted' => 'Unlisted: only people with the link can see it',
        'private' => 'Private: only you can see it',
    ],

    'availability' => [
        'none' => 'Not looking right now',
        'placement' => 'Placement year',
        'internship' => 'Internship',
        'graduate' => 'Graduate role',
        'freelance' => 'Freelance or part time',
    ],

];
