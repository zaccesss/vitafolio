<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * the interface languages. english is the source; each other language has a lang/{code}.json
 * file with the same keys. "html" is the tag written on the page and "dir" its reading direction
 */
class Locales
{
    public const DEFAULT = 'en';

    public const COOKIE = 'locale';

    public const ALL = [
        'en' => ['name' => 'English', 'html' => 'en-GB', 'dir' => 'ltr'],
        'es' => ['name' => 'Español', 'html' => 'es', 'dir' => 'ltr'],
        'fr' => ['name' => 'Français', 'html' => 'fr', 'dir' => 'ltr'],
        'pt_BR' => ['name' => 'Português (Brasil)', 'html' => 'pt-BR', 'dir' => 'ltr'],
        'zh_CN' => ['name' => '简体中文', 'html' => 'zh-CN', 'dir' => 'ltr'],
        'ar' => ['name' => 'العربية', 'html' => 'ar', 'dir' => 'rtl'],
        'ur' => ['name' => 'اردو', 'html' => 'ur', 'dir' => 'rtl'],
    ];

    /**
     * strings the browser scripts show. they are sent with each page in a data block, so the
     * scripts never need a request of their own and the content security policy stays strict
     */
    public const SCRIPT_STRINGS = [
        // app.js
        ':count topic matches.', ':count topics match.', 'System', 'Light', 'Dark', 'Theme: :name. Select to change.',
        'Show', 'Hide', ':count of :max characters', ':count characters', 'Link copied', 'Copy link',
        'Link copied to the clipboard', 'That passkey did not work. Try again or sign in with your password.',
        'Signing in with a passkey was cancelled.', 'Waiting for your passkey…', 'Sign in with a passkey',
        'This device', ':device passkey', 'Adding the passkey was cancelled.',
        'The passkey could not be added. If you already have one for this device, it may be saved already.',
        'Waiting for your device…', 'Add a passkey', 'Please wait…', 'Copy', 'Copied', 'Code copied to the clipboard',
        'Select and copy', 'Diagram :number. Its text version follows.', 'Show the diagram as text',
        // AvatarCropper.vue
        'Photo loaded. Drag it, use the arrow keys or the zoom slider to frame it.',
        'That photo cannot be previewed here. It will still be cropped to the centre when you upload it.',
        'Photo framing. Use the arrow keys to move the photo, plus and minus to zoom.', 'Zoom', 'Reset framing',
        'The circle shows how the photo appears on your profile and CVs.',
        // SectionOrder.vue
        ':section moved to position :position.', ':message Saved.', 'The new order could not be saved. Please try again.',
        'Drag to reorder', 'Move up', 'Move down',
        // LatexStudio.vue
        'Cmd', 'Ctrl', 'LaTeX source',
        'Replace your current LaTeX with this template? Your saved version is kept until you save again.',
        'Template loaded: :name', 'Compiling is not available on this site yet. You can still edit and save your LaTeX.',
        'Downloading the LaTeX engine, about 120 MB the first time. After that your browser keeps it.',
        'Downloading the LaTeX engine: :percent%', 'Compiling…',
        'Compiled. Check the preview, then save to attach the PDF to your CV.',
        'LaTeX reported an error. The log below shows where.',
        'The LaTeX engine could not start. Try again with a recent version of Chrome, Edge, Firefox or Safari.',
        'Saving…', 'Saved at :time. :error', 'Saved at :time. Your compiled PDF is now this CV’s file.',
        'Saved at :time. Compile to attach a PDF to your CV.', 'Saving failed. Check your connection and try again.',
        'Start from a template', 'Choose a template…', 'Engine', 'pdfLaTeX (most templates)',
        'XeLaTeX (system fonts, Unicode)', 'Compile', 'Save', 'You have unsaved changes.', 'All changes saved.',
        'Plain text box', 'PDF preview', 'Download this PDF', 'Compiled PDF preview',
        'Compile to see your PDF here. Everything runs in your browser; your LaTeX never leaves your device until you save.',
        'Compiler log',
    ];

    public static function supported(?string $code): bool
    {
        return is_string($code) && isset(self::ALL[$code]);
    }

    public static function html(?string $code = null): string
    {
        return self::ALL[$code ?? app()->getLocale()]['html'] ?? 'en-GB';
    }

    public static function dir(?string $code = null): string
    {
        return self::ALL[$code ?? app()->getLocale()]['dir'] ?? 'ltr';
    }

    /** the best supported language for an Accept-Language header, in the order the browser prefers */
    public static function fromHeader(?string $header): string
    {
        $wanted = [];
        foreach (explode(',', (string) $header) as $position => $part) {
            $pieces = array_map('trim', explode(';', $part));
            $tag = strtolower(str_replace('_', '-', $pieces[0]));
            if ($tag === '' || $tag === '*') {
                continue;
            }
            $quality = 1.0;
            foreach (array_slice($pieces, 1) as $parameter) {
                if (preg_match('/^q=([01](?:\.\d{0,3})?)$/i', $parameter, $match)) {
                    $quality = (float) $match[1];
                }
            }
            if ($quality > 0) {
                // ties keep the order the browser sent them in
                $wanted[] = [$tag, $quality, $position];
            }
        }
        usort($wanted, fn ($a, $b) => [$b[1], $a[2]] <=> [$a[1], $b[2]]);

        foreach ($wanted as [$tag]) {
            if ($code = self::match($tag)) {
                return $code;
            }
        }

        return self::DEFAULT;
    }

    /** one language tag to a supported code; chinese only matches its simplified script */
    public static function match(string $tag): ?string
    {
        $tag = strtolower(str_replace('_', '-', $tag));
        $primary = explode('-', $tag)[0];

        return match ($primary) {
            'en', 'es', 'fr', 'ar', 'ur' => $primary,
            'pt' => 'pt_BR',
            'zh' => preg_match('/^zh(-hans(-.*)?|-cn|-sg|-my)?$/', $tag) ? 'zh_CN' : null,
            default => null,
        };
    }

    /** a saved choice first, then the cookie, then what the browser asks for */
    public static function forRequest(Request $request): string
    {
        $saved = $request->user()?->locale;
        if (self::supported($saved)) {
            return $saved;
        }
        $cookie = $request->cookie(self::COOKIE);
        if (self::supported($cookie)) {
            return $cookie;
        }

        return self::fromHeader($request->header('Accept-Language'));
    }

    /** @return array<string, string> */
    public static function scriptStrings(): array
    {
        return collect(self::SCRIPT_STRINGS)->mapWithKeys(fn (string $key) => [$key => __($key)])->all();
    }
}
