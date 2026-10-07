<?php

namespace App\Support;

/**
 * the demo clips on the features page, recorded by scripts/demo. each clip lives in public/demo as
 * {clip}.webp, .mp4 and -still.webp, with a -dark copy of each for the dark theme
 */
class Demos
{
    /** @return array<string, array{title: string, text: string, alt: string}> */
    public static function all(): array
    {
        return [
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
        ];
    }
}
