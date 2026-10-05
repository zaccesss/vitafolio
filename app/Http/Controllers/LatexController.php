<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Models\CvDocument;
use App\Support\DocumentStore;
use App\Support\Latex;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LatexController extends Controller
{
    public function edit(Request $request, Cv $cv): View
    {
        Gate::authorize('manage', $cv);
        $cv->load(['user', 'tags', 'projects']);
        $template = (string) $request->query('template', 'classic');

        // starter documents for every template, so switching template never needs a round trip
        $starters = collect(Latex::TEMPLATES)->mapWithKeys(fn ($label, $key) => [$key => Latex::starter($cv, $key)])->all();

        return view('cv.latex', [
            'cv' => $cv,
            'source' => $cv->latex_source ?: $starters[array_key_exists($template, $starters) ? $template : 'classic'],
            'starters' => $starters,
            'templates' => Latex::TEMPLATES,
            'assetsUrl' => config('vitafolio.latex_assets_url'),
        ]);
    }

    /** saves the source plus the compiled pdf, when one is sent, as this cv's file */
    public function update(Request $request, Cv $cv): JsonResponse
    {
        Gate::authorize('manage', $cv);
        $request->validate([
            'source' => ['required', 'string', 'max:200000'],
            'pdf' => ['nullable', 'file', 'max:'.config('vitafolio.limits.document_kb')],
            'engine' => ['nullable', Rule::in(['pdflatex', 'xelatex', 'lualatex'])],
        ]);
        $cv->update(['latex_source' => $request->input('source')]);

        if ($request->hasFile('pdf')) {
            $bytes = (string) file_get_contents($request->file('pdf')->getRealPath());
            // the compiled file is checked by its signature, like any other upload
            abort_unless(str_starts_with($bytes, '%PDF-'), 422, 'That is not a PDF.');
            $replacing = (int) CvDocument::where('cv_id', $cv->id)->value('size');
            if (! $request->user()->canStore(strlen($bytes), $replacing)) {
                return response()->json(['saved' => true, 'savedAt' => now()->format('H:i'), 'pdfError' => CvDocumentController::fullMessage()], 200);
            }
            if (! DocumentStore::put($cv, $bytes, Str::slug($cv->user->name.' '.$cv->title).'.pdf', 'application/pdf', 'latex')) {
                return response()->json(['saved' => true, 'savedAt' => now()->format('H:i'), 'pdfError' => 'Your LaTeX was saved, but the PDF could not be stored. Please try again.'], 200);
            }
        }

        return response()->json(['saved' => true, 'savedAt' => now()->format('H:i')]);
    }
}
