<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Models\User;
use App\Support\Images;
use App\Support\Links;
use App\Support\ViewRecorder;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Mpdf\Mpdf;

class CvController extends Controller
{
    public function show(Request $request, Cv $cv): View
    {
        abort_unless($cv->isVisibleTo($request->user()), 404);
        $cv->load(['user', 'tags', 'projects', 'document']);
        // a printed qr code carries ?src=qr, so scans are counted apart from ordinary visits
        ViewRecorder::record($cv, $request, $request->user(), $request->query('src') === 'qr' ? 'qr' : 'view');

        return view('cv.show', [
            'cv' => $cv,
            'links' => Links::parse($cv->user->links),
            'isOwner' => $request->user()?->id === $cv->user_id,
        ]);
    }

    public function pdf(Request $request, Cv $cv): Response
    {
        abort_unless($cv->isVisibleTo($request->user()), 404);
        $cv->load(['user', 'tags', 'projects']);
        ViewRecorder::record($cv, $request, $request->user(), 'pdf');

        // the photo is embedded as data so mpdf never fetches anything over the network
        $picture = null;
        if ($cv->user->hasAvatar()) {
            $round = Images::circlePng((string) DB::table('users')->where('id', $cv->user_id)->value('avatar'), 300);
            $picture = $round ? 'data:image/png;base64,'.base64_encode($round) : null;
        }

        $html = view('cv.pdf', ['cv' => $cv, 'links' => Links::parse($cv->user->links), 'picture' => $picture])->render();

        $mpdf = new Mpdf([
            'tempDir' => storage_path('framework/cache/mpdf'),
            'margin_top' => 14, 'margin_bottom' => 14, 'margin_left' => 16, 'margin_right' => 16,
            'default_font' => 'dejavusans',
        ]);
        $mpdf->SetTitle($cv->user->name.' CV');
        $mpdf->WriteHTML($html);

        $filename = (preg_replace('/[^A-Za-z0-9_-]/', '', str_replace(' ', '_', $cv->user->name)) ?: 'Vitafolio').'_CV.pdf';

        return response($mpdf->OutputBinaryData(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** svg qr code pointing at the cv, for printed cvs, posters and business cards */
    public function qr(Request $request, Cv $cv): Response
    {
        abort_unless($cv->isVisibleTo($request->user()), 404);
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'svgAddXmlHeader' => true,
        ]);

        return response((new QRCode($options))->render(route('cv.show', ['cv' => $cv, 'src' => 'qr'])), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => $cv->visibility === 'public' && ! $cv->hidden_at ? 'public, max-age=86400' : 'private, no-store',
        ]);
    }

    public function sitemap(): Response
    {
        $cvs = Cv::listed()->get(['cvs.slug', 'cvs.updated_at']);
        // unlisted profiles stay out, the same as unlisted cvs: they are for people with the link
        $profiles = User::where('profile_visibility', 'public')->whereNotNull('email_verified_at')
            ->whereNull('suspended_at')->get(['handle', 'updated_at']);

        return response()->view('sitemap', ['cvs' => $cvs, 'profiles' => $profiles, 'updated' => config('vitafolio.content_updated')], 200, [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
