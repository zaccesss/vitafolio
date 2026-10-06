<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Models\User;
use App\Support\Links;
use App\Support\PdfCv;
use App\Support\ViewRecorder;
use App\Support\WordCv;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CvController extends Controller
{
    public function show(Request $request, Cv $cv): View
    {
        abort_unless($cv->isVisibleTo($request->user()), 404);
        $cv->load(['user', 'tags', 'projects', 'document', 'endorsements' => fn ($q) => $q->shown()->with('endorser')]);
        $viewer = $request->user();
        // a printed qr code carries ?src=qr, so scans are counted apart from ordinary visits
        ViewRecorder::record($cv, $request, $request->user(), $request->query('src') === 'qr' ? 'qr' : 'view');

        return view('cv.show', [
            'cv' => $cv,
            'links' => Links::parse($cv->user->links),
            'isOwner' => $viewer?->id === $cv->user_id,
            // the viewer's own endorsement, whatever its status, so they can edit or withdraw it
            'myEndorsement' => $viewer ? $cv->endorsements()->where('endorser_id', $viewer->id)->first() : null,
            'pendingEndorsements' => $viewer?->id === $cv->user_id
                ? $cv->endorsements()->where('status', 'pending')->whereNull('hidden_at')->count() : 0,
        ]);
    }

    /** the cv's cover letter, on its own page in the cv's theme */
    public function letter(Request $request, Cv $cv): View
    {
        abort_unless($cv->isVisibleTo($request->user()) && $cv->hasCoverLetter(), 404);
        $cv->load('user');

        return view('cv.letter', [
            'cv' => $cv,
            'isOwner' => $request->user()?->id === $cv->user_id,
        ]);
    }

    /** the cv as an editable word document, built from the same content as the pdf */
    public function word(Request $request, Cv $cv): Response
    {
        abort_unless($cv->isVisibleTo($request->user()), 404);
        $cv->load(['user', 'tags', 'projects']);

        return $this->wordResponse(WordCv::build($cv), self::filename($cv, 'CV', 'docx'));
    }

    public function letterWord(Request $request, Cv $cv): Response
    {
        abort_unless($cv->isVisibleTo($request->user()) && $cv->hasCoverLetter(), 404);
        $cv->load('user');

        return $this->wordResponse(WordCv::letter($cv), self::filename($cv, 'Cover_letter', 'docx'));
    }

    public function pdf(Request $request, Cv $cv): Response
    {
        abort_unless($cv->isVisibleTo($request->user()), 404);
        $cv->load(['user', 'tags', 'projects']);
        ViewRecorder::record($cv, $request, $request->user(), 'pdf');

        return $this->pdfResponse($cv, false);
    }

    public function letterPdf(Request $request, Cv $cv): Response
    {
        abort_unless($cv->isVisibleTo($request->user()) && $cv->hasCoverLetter(), 404);
        $cv->load('user');

        return $this->pdfResponse($cv, true);
    }

    /** the cv and its letter share one pdf layout, so both carry the same theme, accent and font */
    private function pdfResponse(Cv $cv, bool $letter): Response
    {
        return response(PdfCv::build($cv, $letter), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.self::filename($cv, $letter ? 'Cover_letter' : 'CV', 'pdf').'"',
        ]);
    }

    private function wordResponse(string $bytes, string $filename): Response
    {
        return response($bytes, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** a download named after its owner, with anything a header could choke on removed */
    private static function filename(Cv $cv, string $kind, string $extension): string
    {
        return (preg_replace('/[^A-Za-z0-9_-]/', '', str_replace(' ', '_', $cv->user->name)) ?: 'Vitafolio').'_'.$kind.'.'.$extension;
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
