<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /** remembers the chosen language: on the account when signed in and in a cookie either way */
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(Locales::ALL))],
            'from' => ['nullable', 'string', 'max:2000'],
        ]);

        $request->user()?->forceFill(['locale' => $data['locale']])->save();
        app()->setLocale($data['locale']);

        return redirect()->to(self::safeReturn($data['from'] ?? url()->previous()))
            ->withCookie(cookie(Locales::COOKIE, $data['locale'], 60 * 24 * 365))
            ->with('status', __('The language is now :name.', ['name' => Locales::ALL[$data['locale']]['name']]));
    }

    /**
     * the language menu sends a visitor with no stored session here, with the language they pressed.
     * with no language named this page prints the form token and lists the languages. someone signed
     * in always gets the page
     */
    public function choose(Request $request): View|RedirectResponse
    {
        $asked = $request->query('locale');
        $chosen = is_string($asked) && isset(Locales::ALL[$asked]) ? $asked : null;

        // a signed-out visitor's choice only sets a preference cookie, so it is applied at once and the
        // menu stays one press. an account's language is changed by the posted form alone, behind its token
        if ($chosen !== null && $request->user() === null) {
            app()->setLocale($chosen);

            return redirect()->to(self::safeReturn(url()->previous()))
                ->withCookie(cookie(Locales::COOKIE, $chosen, 60 * 24 * 365))
                ->with('status', __('The language is now :name.', ['name' => Locales::ALL[$chosen]['name']]));
        }

        return view('pages.language', [
            'chosen' => $chosen,
            // the page the visitor came from, carried through the form so they land back on it
            'from' => self::safeReturn(url()->previous()),
        ]);
    }

    /** only a page on this site is a safe place to return to. the language page itself never is */
    private static function safeReturn(string $back): string
    {
        $home = url('/');
        $onSite = $back === $home || str_starts_with($back, $home.'/');

        return $onSite && strtok($back, '?') !== route('locale') ? $back : route('home');
    }
}
