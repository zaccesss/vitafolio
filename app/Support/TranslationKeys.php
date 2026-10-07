<?php

namespace App\Support;

use App\Listeners\SendSecurityNotice;
use App\Models\Endorsement;
use App\Models\Report;
use Illuminate\Support\Facades\File;
use ReflectionClassConstant;

/**
 * every english interface string, read from the code itself. lang/en.json is written from this list,
 * so a string added to a view or controller can never be missing from the source file translators see
 */
class TranslationKeys
{
    /** strings the framework and its packages translate through the json files */
    public const FRAMEWORK = [
        // password reset and email verification emails
        'Reset Password Notification', 'Reset your password', 'Reset Password',
        'You are receiving this email because we received a password reset request for your account.',
        'This password reset link will expire in :count minutes.',
        'If you did not request a password reset, no further action is required.',
        'Verify Email Address', 'Verify your email address', 'Please click the button below to verify your email address.',
        'If you did not create an account, no further action is required.',
        'Hello!', 'Whoops!', 'Regards,', 'All rights reserved.',
        "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:",
        // fortify and passkeys
        'The provided password was incorrect.', 'The provided two factor authentication code was invalid.',
        'The provided two factor recovery code was invalid.', 'Invalid credential format.',
        'Passkey registration session expired. Please try again.', 'Passkey verification session expired. Please try again.',
        // authorisation and error responses
        'This action is unauthorized.', 'Forbidden', 'Not Found', 'Page Expired', 'Server Error', 'Service Unavailable',
        'Too Many Requests', 'Unauthorized',
    ];

    /** @return list<string> */
    public static function all(): array
    {
        return collect([...self::fromSource(), ...self::dynamic(), ...self::FRAMEWORK, ...Locales::SCRIPT_STRINGS])
            ->unique()->sort(SORT_STRING | SORT_FLAG_CASE)->values()->all();
    }

    /** keys written as literals in __(), trans_choice() and @lang() calls in views and php code */
    public static function fromSource(): array
    {
        $files = [
            ...File::allFiles(resource_path('views')),
            ...File::allFiles(app_path()),
        ];
        $keys = [];
        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }
            $source = $file->getContents();
            preg_match_all("/(?:\b__|\btrans_choice|@lang|->label)\(\s*'((?:[^'\\\\]|\\\\.)*)'/", $source, $single);
            preg_match_all('/(?:\b__|\btrans_choice|@lang)\(\s*"((?:[^"\\\\$]|\\\\.)*)"/', $source, $double);
            foreach ($single[1] as $key) {
                $keys[] = str_replace(["\\'", '\\\\'], ["'", '\\'], $key);
            }
            foreach ($double[1] as $key) {
                $keys[] = stripcslashes($key);
            }
        }

        // a group key such as validation.required lives in its php file, not in the json one
        return array_values(array_filter($keys, fn (string $key) => $key !== '' && ! preg_match('/^[a-z_]+\.[a-z_.]+$/', $key)));
    }

    /** labels that live in config and constants and are translated where they are shown */
    public static function dynamic(): array
    {
        $constant = fn (string $class, string $name) => (new ReflectionClassConstant($class, $name))->getValue();

        return [
            ...array_values(config('vitafolio.themes')),
            ...array_column(config('vitafolio.accents'), 'label'),
            ...array_values(config('vitafolio.fonts')),
            ...array_values(config('vitafolio.profile_visibility')),
            ...array_values(config('vitafolio.visibility')),
            ...array_values(config('vitafolio.availability')),
            ...array_values(Report::REASONS),
            ...array_merge(...array_values(HelpTopics::ALL)),
            ...array_values(ViewRecorder::KINDS),
            ...array_values(Latex::TEMPLATES),
            ...array_merge(...array_values($constant(SendSecurityNotice::class, 'NOTICES'))),
            // moderation counts, analytics cards and the editor checklist
            'Accounts', 'CVs', 'Public CVs', 'Open reports', 'Suspended accounts',
            'CV views', 'Profile views', 'PDF downloads', 'QR code scans',
            'Headline', 'Summary', 'Skills', 'Experience', 'Projects', 'Education', 'Profile photo', 'Uploaded or LaTeX file',
            'New sign-in', 'Your account was signed in from a browser it has not used before: :agent.',
            ':provider connected', ':provider can now be used to sign in to your account.',
            ':provider disconnected', ':provider can no longer be used to sign in to your account.',
            ':app enquiry', 'Message about your CV on :app',
            'Public', 'Unlisted', 'Private',
            ...array_values(Endorsement::RELATIONSHIPS),
            ...array_values(Endorsement::STATUSES),
        ];
    }

    /** keys used in the browser scripts, so the page can be checked against SCRIPT_STRINGS */
    public static function fromScripts(): array
    {
        $keys = [];
        foreach (File::allFiles(resource_path('js')) as $file) {
            preg_match_all("/\bt\(\s*'((?:[^'\\\\]|\\\\.)*)'/", $file->getContents(), $matches);
            array_push($keys, ...array_map(fn ($key) => str_replace("\\'", "'", $key), $matches[1]));
        }

        return array_values(array_unique($keys));
    }
}
