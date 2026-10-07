<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /** remembers the chosen language: on the account when signed in and in a cookie either way */
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', 'string', Rule::in(array_keys(Locales::ALL))]]);

        $request->user()?->forceFill(['locale' => $data['locale']])->save();
        app()->setLocale($data['locale']);

        // only a page on this site is a safe place to return to
        $back = url()->previous();
        $home = url('/');
        $target = ($back === $home || str_starts_with($back, $home.'/')) && $back !== route('locale') ? $back : route('home');

        return redirect()->to($target)
            ->withCookie(cookie(Locales::COOKIE, $data['locale'], 60 * 24 * 365))
            ->with('status', __('The language is now :name.', ['name' => Locales::ALL[$data['locale']]['name']]));
    }
}
