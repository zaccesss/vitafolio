<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/** the settings pages that only show forms; each form posts to the controller that owns it */
class SettingsController extends Controller
{
    public const PAGES = ['photo', 'handle', 'security', 'passkeys', 'connected', 'data'];

    public function show(Request $request, string $page): View
    {
        abort_unless(in_array($page, self::PAGES, true), 404);

        return view('settings.'.$page, ['user' => $request->user()]);
    }
}
