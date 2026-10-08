<?php

namespace App\Support;

/**
 * the demo clips on the features page, recorded by scripts/demo. each clip lives in public/demo as
 * {clip}.webp, .mp4 and -still.webp, with a -dark copy of each for the dark theme
 */
class Demos
{
    /** the clip names, in the order they are shown; the route accepts only these */
    public const CLIPS = ['signup', 'build', 'share', 'compile', 'profile', 'check', 'jobs', 'support'];

    /** @return array<string, array{title: string, text: string, alt: string}> */
    public static function all(): array
    {
        return [
            'signup' => [
                'title' => __('Create an account'),
                'text' => __('Sign up with an email address or with Google, Microsoft or GitHub, then confirm your email.'),
                'alt' => __('The sign-up form is filled in, the Google, GitHub and Microsoft buttons are shown, the account is created, the email address is confirmed and the dashboard opens with a first CV ready.'),
            ],
            'build' => [
                'title' => __('Build a CV'),
                'text' => __('Create a named CV, write each section in plain text and save it.'),
                'alt' => __('A new CV is created, then its headline, profile, skills, experience and education are typed into the editor and saved.'),
            ],
            'share' => [
                'title' => __('Style and share it'),
                'text' => __('Pick a layout, colour and font, choose who can see it, then share one link.'),
                'alt' => __('A CV is given the Modern layout, a teal accent and public visibility, then its published page with a QR code and the directory are shown.'),
            ],
            'compile' => [
                'title' => __('Compile a PDF from LaTeX'),
                'text' => __('Start from a template and compile it in your browser, then save the PDF to your CV.'),
                'alt' => __('The LaTeX editor compiles a template in the browser, the PDF appears in the preview and is saved to the CV.'),
            ],
            'profile' => [
                'title' => __('Photo, handle and sign-ins'),
                'text' => __('Upload and frame a photo, choose your handle and connect the sites you sign in with.'),
                'alt' => __('A photo is chosen, zoomed and dragged into the circle, then uploaded. The handle changes to alex-morgan and Connected accounts shows Google and GitHub connected with Microsoft ready to connect.'),
            ],
            'check' => [
                'title' => __('Check a CV'),
                'text' => __('See how a tracking system reads your CV, with a score, gaps and fixes for a job advert.'),
                'alt' => __('A Vitafolio CV is chosen and a job advert is pasted in. The report shows the overall score, the score for each area, suggestions and which advert keywords the CV has and lacks.'),
            ],
            'jobs' => [
                'title' => __('Find and track jobs'),
                'text' => __('Search student roles by kind, field and place, save one and track every application.'),
                'alt' => __('The Jobs page is filtered to hardware internships and a role is saved. On My applications its status changes to Applied and a role found elsewhere is added by hand.'),
            ],
            'support' => [
                'title' => __('Get help'),
                'text' => __('Open a support ticket and follow the conversation until it is sorted.'),
                'alt' => __('A support ticket about the order of CV sections is opened and given a reference. The tickets list and an earlier conversation with a reply from the support team are then shown.'),
            ],
        ];
    }
}
