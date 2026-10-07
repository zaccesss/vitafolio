<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Models\JobListing;
use App\Support\Ats\AtsReport;
use App\Support\Ats\ResumeText;
use App\Support\Ats\UnreadableResume;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Checks a CV the way an applicant tracking system reads it. An uploaded file is read in memory
 * and discarded; only the report is kept, in the session, so it can be downloaded.
 */
class CvCheckController extends Controller
{
    public function show(Request $request): View
    {
        // "check my CV against this job" opens the page with that job's advert already filled in
        $job = $request->integer('job') ? JobListing::query()->open()->find($request->integer('job')) : null;

        return view('check', [
            'cvs' => $request->user()->cvs()->orderBy('title')->get(['id', 'title']),
            'report' => null,
            'advert' => $job ? trim($job->title."\n".$job->description) : null,
            'job' => $job,
        ]);
    }

    public function check(Request $request): View
    {
        $data = $request->validate([
            'source' => ['required', Rule::in(['file', 'cv'])],
            'resume' => ['exclude_unless:source,file', 'required', 'file', 'max:5120', 'mimes:pdf,docx'],
            'cv' => ['exclude_unless:source,cv', 'required', 'integer'],
            'job_advert' => ['nullable', 'string', 'max:10000'],
        ]);

        if ($data['source'] === 'cv') {
            $cv = Cv::whereKey($data['cv'])->where('user_id', $request->user()->id)->firstOrFail();
            $text = ResumeText::fromCv($cv);
        } else {
            $file = $request->file('resume');
            try {
                $text = ResumeText::fromFile($file->getRealPath(), $file->getClientOriginalExtension());
            } catch (UnreadableResume $e) {
                throw ValidationException::withMessages(['resume' => __($e->getMessage())]);
            }
        }

        $report = AtsReport::analyse($text, $data['job_advert'] ?? null);
        $request->session()->put('cv_check', ['json' => $report->toArray(), 'markdown' => $report->toMarkdown()]);

        return view('check', [
            'cvs' => $request->user()->cvs()->orderBy('title')->get(['id', 'title']),
            'report' => $report->toArray(),
            'advert' => null,
            'job' => null,
        ]);
    }

    public function download(Request $request, string $format): Response
    {
        $saved = $request->session()->get('cv_check');
        abort_if($saved === null, 404);

        return $format === 'json'
            ? response(json_encode($saved['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n", 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="parsed.json"',
            ])
            : response($saved['markdown'], 200, [
                'Content-Type' => 'text/markdown; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="report.md"',
            ]);
    }
}
