<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\CvDocumentController;
use App\Http\Controllers\CvEditorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\LatexController;
use App\Http\Controllers\OgImageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SocialAuthController;
use App\Support\HelpTopics;
use Illuminate\Support\Facades\Route;

Route::get('/', DirectoryController::class)->name('home');

Route::view('/about', 'pages.about')->name('about');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/cookies', 'pages.cookies')->name('cookies');
Route::view('/terms', 'pages.terms')->name('terms');
Route::view('/accessibility', 'pages.accessibility')->name('accessibility');

Route::get('/manifest.webmanifest', [SiteController::class, 'manifest'])->name('manifest');
Route::get('/robots.txt', [SiteController::class, 'robots'])->name('robots');
Route::get('/.well-known/security.txt', [SiteController::class, 'securityTxt'])->name('security.txt');
// browsers and password managers send people here after a breach alert
Route::redirect('/.well-known/change-password', '/settings/security', 302);
Route::get('/indexnow.txt', [SiteController::class, 'indexNowKey'])->name('indexnow.key');
Route::get('/offline', [SiteController::class, 'offline'])->name('offline');

// sign-in through another site; the same two routes also connect one to a signed-in account
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->middleware('throttle:20,1')->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->middleware('throttle:20,1')->name('social.callback');

Route::post('/cron', CronController::class)->middleware('throttle:6,1')->name('cron');

Route::view('/contact', 'pages.contact')->name('contact.show');
Route::view('/support', 'pages.support')->name('support');
Route::view('/copyright', 'pages.copyright')->name('copyright');
Route::view('/contact/sent', 'pages.contact-sent')->name('contact.sent');
Route::view('/features', 'pages.features')->name('features');
Route::view('/changelog', 'pages.changelog')->name('changelog');
Route::view('/docs', 'pages.docs')->name('docs');
Route::view('/help', 'help.index')->name('help');
Route::get('/help/{topic}', fn (string $topic) => view('help.'.$topic))
    ->whereIn('topic', array_keys(HelpTopics::ALL))->name('help.topic');
Route::post('/contact', [ContactController::class, 'site'])->middleware('throttle:messages')->name('contact');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/analytics', AnalyticsController::class)->name('analytics');

    Route::post('/cvs', [CvEditorController::class, 'store'])->name('cvs.store');
    Route::prefix('/cvs/{cv}')->name('cvs.')->group(function () {
        Route::get('/edit/{tab?}', [CvEditorController::class, 'edit'])
            ->whereIn('tab', ['details', 'projects', 'file', 'settings', 'import'])->name('edit');
        Route::put('/details', [CvEditorController::class, 'updateDetails'])->name('details');
        Route::put('/settings', [CvEditorController::class, 'updateSettings'])->name('settings');
        Route::put('/publish', [CvEditorController::class, 'publish'])->name('publish');
        Route::put('/order', [CvEditorController::class, 'updateOrder'])->name('order');
        Route::post('/projects', [ProjectController::class, 'store'])->middleware('throttle:uploads')->name('projects.store');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->middleware('throttle:uploads')->name('projects.update');
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::get('/latex', [LatexController::class, 'edit'])->name('latex');
        Route::put('/latex', [LatexController::class, 'update'])->middleware('throttle:uploads')->name('latex.update');
        Route::post('/document', [CvDocumentController::class, 'store'])->middleware('throttle:uploads')->name('document');
        Route::delete('/document', [CvDocumentController::class, 'destroy'])->name('document.delete');
        Route::post('/import', [CvEditorController::class, 'import'])->middleware('throttle:uploads')->name('import');
        Route::get('/export.json', [CvEditorController::class, 'exportJson'])->name('export');
        Route::post('/duplicate', [CvEditorController::class, 'duplicate'])->name('duplicate');
        Route::delete('/', [CvEditorController::class, 'destroy'])->name('destroy');
    });

    // settings: public profile, photo and handle, then sign-in and data; old addresses redirect here
    Route::redirect('/settings', '/settings/profile');
    Route::redirect('/profile', '/settings/profile');
    Route::redirect('/account', '/settings/account');
    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/settings/account', [AccountController::class, 'edit'])->name('account');
    foreach (SettingsController::PAGES as $page) {
        Route::get('/settings/'.$page, [SettingsController::class, 'show'])->defaults('page', $page)->name('settings.'.$page);
    }
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/handle', [ProfileController::class, 'updateHandle'])->middleware('throttle:6,1')->name('profile.handle');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->middleware('throttle:uploads')->name('profile.avatar');
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');

    Route::put('/account/password', [AccountController::class, 'setPassword'])->middleware('throttle:6,1')->name('account.password');
    Route::delete('/account/connected/{provider}', [SocialAuthController::class, 'disconnect'])->name('social.disconnect');
    Route::post('/account/connected/{provider}/photo', [SocialAuthController::class, 'usePhoto'])->middleware('throttle:uploads')->name('social.photo');
    Route::get('/account/passkeys', [AccountController::class, 'passkeys'])->middleware('password.confirm')->name('account.passkeys');
    Route::get('/settings/sessions', [AccountController::class, 'sessions'])->name('settings.sessions');
    Route::post('/account/sessions', [AccountController::class, 'endOtherSessions'])->middleware('throttle:6,1')->name('account.sessions');
    Route::delete('/account/sessions/{id}', [AccountController::class, 'endSession'])->middleware('password.confirm')->name('account.sessions.end');
    Route::get('/account/export.json', [AccountController::class, 'export'])->name('account.export');
    Route::delete('/account', [AccountController::class, 'destroy'])->middleware('password.confirm')->name('account.destroy');
});

Route::middleware(['auth', 'verified', 'can:admin'])->prefix('/admin')->name('admin.')->group(function () {
    Route::get('/', [ModerationController::class, 'index'])->name('index');
    Route::post('/cvs/{cv}/hide', [ModerationController::class, 'hide'])->name('hide');
    Route::post('/cvs/{cv}/restore', [ModerationController::class, 'restore'])->name('restore');
    Route::post('/reports/{report}/dismiss', [ModerationController::class, 'dismiss'])->name('dismiss');
    Route::post('/users/{user}/suspend', [ModerationController::class, 'suspend'])->name('suspend');
    Route::delete('/users/{user}/avatar', [ModerationController::class, 'removeAvatar'])->name('avatar.remove');
});

Route::get('/sitemap.xml', [CvController::class, 'sitemap'])->name('sitemap');
Route::get('/cv/{cv}', [CvController::class, 'show'])->name('cv.show');
Route::get('/cv/{cv}/og.png', OgImageController::class)->name('cv.og');
Route::get('/cv/{cv}/qr.svg', [CvController::class, 'qr'])->name('cv.qr');
Route::post('/cv/{cv}/report', [ReportController::class, 'store'])->middleware('throttle:reports')->name('cv.report');
Route::get('/cv/{cv}/pdf', [CvController::class, 'pdf'])->middleware('throttle:pdf')->name('cv.pdf');
Route::get('/cv/{cv}/file', [CvDocumentController::class, 'show'])->middleware('throttle:pdf')->name('cv.file');
Route::post('/cv/{cv}/message', [ContactController::class, 'cv'])->middleware('throttle:messages')->name('cv.message');
Route::get('/avatar/{user}', AvatarController::class)->name('avatar');
// public profile pages; an old handle redirects here for a month after a change
Route::get('/@{handle}', [ProfileController::class, 'show'])->where('handle', '[a-z0-9-]{3,40}')->name('profile.show');
