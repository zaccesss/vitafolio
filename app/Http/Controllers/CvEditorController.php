<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Support\JsonResume;
use App\Support\Tags;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CvEditorController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Cv::class);
        $data = $request->validate(['title' => ['required', 'string', 'max:80']]);

        $cv = $request->user()->cvs()->create([
            'title' => $data['title'],
            'slug' => Cv::uniqueSlug($request->user()->name.' '.$data['title']),
            // a new cv starts private so nothing half finished is ever listed
            'visibility' => 'private',
        ]);

        return redirect()->route('cvs.edit', $cv)->with('status', 'New CV created. It stays private until you change who can see it.');
    }

    public function edit(Request $request, Cv $cv, string $tab = 'details'): View
    {
        Gate::authorize('manage', $cv);
        $cv->load(['tags', 'document']);

        return view('cv.edit', ['cv' => $cv, 'tab' => $tab, 'user' => $request->user()]);
    }

    public function updateDetails(Request $request, Cv $cv): RedirectResponse
    {
        Gate::authorize('manage', $cv);
        $data = $request->validate([
            'headline' => ['nullable', 'string', 'max:120'],
            'key_language' => ['nullable', 'string', 'max:60'],
            'skills' => ['nullable', 'string', 'max:1500'],
            'profile' => ['nullable', 'string', 'max:3000'],
            'experience' => ['nullable', 'string', 'max:5000'],
            'education' => ['nullable', 'string', 'max:5000'],
        ]);

        $cv->update(collect($data)->except('skills')->all());
        Tags::sync($cv, Tags::parse($data['skills'] ?? ''));
        $cv->touch();

        return redirect()->route('cvs.edit', [$cv, 'details'])->with('status', 'Your CV has been saved.');
    }

    /** the letter is stored on the cv itself, so it follows the cv's theme and visibility */
    public function updateLetter(Request $request, Cv $cv): RedirectResponse
    {
        Gate::authorize('manage', $cv);
        $data = $request->validate([
            'letter_to' => ['nullable', 'string', 'max:160'],
            'cover_letter' => ['nullable', 'string', 'max:6000'],
        ]);
        $cv->update($data);

        return redirect()->route('cvs.edit', [$cv, 'letter'])
            ->with('status', filled($data['cover_letter'] ?? null) ? 'Your cover letter has been saved.' : 'This CV has no cover letter now.');
    }

    /** the order chosen by dragging sections in the editor; unknown names are ignored */
    public function updateOrder(Request $request, Cv $cv): JsonResponse|RedirectResponse
    {
        Gate::authorize('manage', $cv);
        $data = $request->validate([
            'order' => ['required', 'array', 'max:6'],
            'order.*' => ['string', Rule::in(['profile', 'experience', 'projects', 'education', 'skills', 'links'])],
        ]);
        $cv->update(['section_order' => array_values(array_unique($data['order']))]);

        return $request->expectsJson()
            ? response()->json(['saved' => true])
            : redirect()->route('cvs.edit', [$cv, 'details'])->with('status', 'Section order saved.');
    }

    /** makes a private or unlisted cv public in one step, from the dashboard or the editor */
    public function publish(Cv $cv): RedirectResponse
    {
        Gate::authorize('manage', $cv);
        $cv->forceFill(['visibility' => 'public'])->save();

        return back()->with('status', $cv->title.' is now public and listed in Browse CVs.');
    }

    public function updateSettings(Request $request, Cv $cv): RedirectResponse
    {
        Gate::authorize('manage', $cv);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn(['edit', 'new', 'export']), Rule::unique('cvs', 'slug')->ignore($cv->id)],
            'visibility' => ['required', Rule::in(array_keys(config('vitafolio.visibility')))],
            'theme' => ['required', Rule::in(array_keys(config('vitafolio.themes')))],
            'accent' => ['required', Rule::in(array_keys(config('vitafolio.accents')))],
            'font' => ['required', Rule::in(array_keys(config('vitafolio.fonts')))],
            'show_email' => ['nullable', 'boolean'],
        ], [
            'slug.regex' => 'Use lowercase letters, numbers and single hyphens only.',
            'slug.unique' => 'That address is already taken. Try adding a word.',
        ]);
        $data['show_email'] = $request->boolean('show_email');
        $cv->update($data);

        return redirect()->route('cvs.edit', [$cv, 'settings'])->with('status', 'Settings saved.');
    }

    public function import(Request $request, Cv $cv): RedirectResponse
    {
        Gate::authorize('manage', $cv);
        $request->validate([
            'resume' => ['required_without:resume_text', 'nullable', 'file', 'max:512', 'mimetypes:application/json,text/plain'],
            'resume_text' => ['required_without:resume', 'nullable', 'string', 'max:200000'],
        ]);
        $raw = $request->hasFile('resume') ? (string) file_get_contents($request->file('resume')->getRealPath()) : (string) $request->input('resume_text');
        $data = json_decode($raw, true, 32);
        if (! is_array($data) || (! isset($data['basics']) && ! isset($data['work']))) {
            throw ValidationException::withMessages(['resume' => 'That does not look like a JSON Resume file.']);
        }

        $fields = JsonResume::import($data);
        $cv->update(collect($fields)->only(['headline', 'profile', 'experience', 'education'])->all());
        if (isset($fields['skills'])) {
            Tags::sync($cv, Tags::parse($fields['skills']));
        }
        // profile details are only filled where the profile is still empty, never overwritten
        $user = $request->user();
        $user->fill(collect(['location' => $fields['location'] ?? null, 'links' => $fields['links'] ?? null])
            ->filter(fn ($v, $k) => filled($v) && blank($user->{$k}))->all())->save();

        return redirect()->route('cvs.edit', [$cv, 'details'])
            ->with('status', 'Imported '.count($fields).' sections. Check everything below before you share this CV.');
    }

    public function exportJson(Cv $cv): JsonResponse
    {
        Gate::authorize('manage', $cv);
        $cv->load(['tags', 'user', 'projects']);

        return response()->json(JsonResume::export($cv), 200, [
            'Content-Disposition' => 'attachment; filename="'.$cv->slug.'-resume.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function duplicate(Request $request, Cv $cv): RedirectResponse
    {
        Gate::authorize('manage', $cv);
        Gate::authorize('create', Cv::class);

        $copy = DB::transaction(function () use ($cv, $request) {
            $copy = $cv->replicate(['view_count']);
            $copy->title = Str::limit($cv->title.' (copy)', 80, '');
            $copy->slug = Cv::uniqueSlug($request->user()->name.' '.$copy->title);
            $copy->visibility = 'private';
            $copy->save();
            $copy->tags()->sync($cv->tags()->pluck('tags.id'));
            foreach ($cv->projects as $project) {
                $copy->projects()->create($project->only(['position', 'title', 'description', 'url', 'media_url', 'media_type', 'media_public_id', 'media_size', 'media_alt']));
            }

            return $copy;
        });

        return redirect()->route('cvs.edit', $copy)->with('status', 'Copy created. It stays private until you change its visibility.');
    }

    public function destroy(Request $request, Cv $cv): RedirectResponse
    {
        Gate::authorize('manage', $cv);
        $request->validate(['confirm_title' => ['required', Rule::in([$cv->title])]], ['confirm_title.in' => 'Type the CV name exactly to confirm.']);
        $title = $cv->title;
        $cv->delete();

        return redirect()->route('dashboard')->with('status', '"'.$title.'" has been deleted.');
    }
}
