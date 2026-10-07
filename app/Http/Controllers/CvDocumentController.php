<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Models\CvDocument;
use App\Support\DocumentStore;
use App\Support\ViewRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CvDocumentController extends Controller
{
    private const TYPES = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    public function store(Request $request, Cv $cv): RedirectResponse
    {
        Gate::authorize('manage', $cv);
        $request->validate(['document' => ['required', 'file', 'max:'.config('vitafolio.limits.document_kb')]],
            ['document.max' => 'CV files can be up to '.intdiv((int) config('vitafolio.limits.document_kb'), 1024).' MB.']);
        $file = $request->file('document');
        $bytes = (string) file_get_contents($file->getRealPath());

        // the type comes from the file's own signature, never from its name or the browser
        $mime = match (true) {
            str_starts_with($bytes, '%PDF-') => 'application/pdf',
            str_starts_with($bytes, "PK\x03\x04") && str_contains($bytes, 'word/document.xml') => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => null,
        };
        if ($mime === null) {
            throw ValidationException::withMessages(['document' => __('Upload a PDF or a Word (.docx) file.')]);
        }

        $base = trim((string) preg_replace('/[^A-Za-z0-9 _.-]/', '', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))) ?: 'CV';
        $filename = mb_substr($base, 0, 120).'.'.self::TYPES[$mime];

        $replacing = (int) CvDocument::where('cv_id', $cv->id)->value('size');
        if (! $request->user()->canStore(strlen($bytes), $replacing)) {
            throw ValidationException::withMessages(['document' => self::fullMessage()]);
        }
        if (! DocumentStore::put($cv, $bytes, $filename, $mime, 'upload')) {
            throw ValidationException::withMessages(['document' => __('The upload failed. Please try again.')]);
        }

        return redirect()->route('cvs.edit', [$cv, 'file'])->with('status', __('Your CV file has been uploaded.'));
    }

    public function destroy(Cv $cv): RedirectResponse
    {
        Gate::authorize('manage', $cv);
        DocumentStore::delete($cv);

        return redirect()->route('cvs.edit', [$cv, 'file'])->with('status', __('Your CV file has been removed.'));
    }

    /** shown when an upload would take an account past its storage allowance */
    public static function fullMessage(): string
    {
        return __('Your account has used its :size MB of storage. Remove an old CV file or project video, then try again.', ['size' => config('vitafolio.limits.storage_mb')]);
    }

    public function show(Request $request, Cv $cv): Response
    {
        abort_unless($cv->isVisibleTo($request->user()), 404);
        $document = CvDocument::where('cv_id', $cv->id)->firstOrFail(['id', 'filename', 'mime', 'storage', 'public_id']);
        $bytes = DocumentStore::read($document);
        ViewRecorder::record($cv, $request, $request->user(), 'file');
        // a file that cannot be fetched right now is a temporary fault, not a missing page
        abort_if($bytes === null, 503);

        return response($bytes, 200, [
            'Content-Type' => $document->mime,
            // pdfs open in the browser's viewer; word files always download
            'Content-Disposition' => ($document->isPdf() ? 'inline' : 'attachment').'; filename="'.$document->filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            // nothing in an uploaded file may load or run anything. Chrome's pdf viewer cannot open inside a
            // sandbox, so a pdf goes without one; it still cannot reach the site, since the viewer runs pdf
            // code apart from the page and nosniff stops the file being treated as html. word files download
            'Content-Security-Policy' => ($document->isPdf() ? '' : 'sandbox; ')."default-src 'none'; object-src 'self'; style-src 'unsafe-inline'; frame-ancestors 'self'",
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
