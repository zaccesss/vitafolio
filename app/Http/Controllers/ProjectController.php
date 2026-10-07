<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Models\Project;
use App\Support\Cloudinary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    private const IMAGE_TYPES = 'image/jpeg,image/png,image/webp,image/gif';

    private const VIDEO_TYPES = 'video/mp4,video/webm,video/quicktime';

    public function store(Request $request, Cv $cv): RedirectResponse
    {
        Gate::authorize('manage', $cv);
        if ($cv->projects()->count() >= (int) config('vitafolio.max_projects_per_cv')) {
            return back()->with('error', __('A CV can show up to :count projects.', ['count' => config('vitafolio.max_projects_per_cv')]));
        }
        $data = $this->validated($request);
        // checked before the project exists, so a rejected file never leaves a half-made project
        $this->checkMedia($request, null);
        $project = $cv->projects()->create($data + ['position' => (int) $cv->projects()->max('position') + 1]);
        $this->attachMedia($request, $project);
        $cv->touch();

        return redirect()->route('cvs.edit', [$cv, 'projects'])->with('status', __('Project added.'));
    }

    public function update(Request $request, Cv $cv, Project $project): RedirectResponse
    {
        Gate::authorize('manage', $cv);
        abort_unless($project->cv_id === $cv->id, 404);
        $project->update($this->validated($request, 'project-'.$project->id));
        if ($request->boolean('remove_media')) {
            $this->removeMedia($project);
        }
        $this->attachMedia($request, $project, 'project-'.$project->id);
        $cv->touch();

        return redirect()->route('cvs.edit', [$cv, 'projects'])->with('status', __('Project saved.'));
    }

    public function destroy(Cv $cv, Project $project): RedirectResponse
    {
        Gate::authorize('manage', $cv);
        abort_unless($project->cv_id === $cv->id, 404);
        $this->removeMedia($project);
        $project->delete();

        return redirect()->route('cvs.edit', [$cv, 'projects'])->with('status', __('Project removed.'));
    }

    private function validated(Request $request, string $bag = 'default'): array
    {
        return $request->validateWithBag($bag, [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1500'],
            'url' => ['nullable', 'url:http,https', 'max:300'],
            'media' => ['nullable', 'file', 'max:'.config('vitafolio.limits.video_kb'), 'mimetypes:'.self::IMAGE_TYPES.','.self::VIDEO_TYPES],
            'media_alt' => ['nullable', 'required_with:media', 'string', 'max:200'],
        ], ['media_alt.required_with' => __('Describe the image or video for people who cannot see it.')]);
    }

    /** the per-file and per-account limits, checked before anything is uploaded */
    private function checkMedia(Request $request, ?Project $project, string $bag = 'default'): void
    {
        if (! $request->hasFile('media')) {
            return;
        }
        $fail = fn (string $message) => throw ValidationException::withMessages(['media' => $message])->errorBag($bag);
        if (! Cloudinary::enabled()) {
            $fail(__('Image and video uploads are not available on this site yet.'));
        }
        $file = $request->file('media');
        $video = str_starts_with((string) $file->getMimeType(), 'video/');
        $limit = (int) config($video ? 'vitafolio.limits.video_kb' : 'vitafolio.limits.image_kb') * 1024;
        if ($file->getSize() > $limit) {
            $fail(__('Images can be up to :image MB. Videos can be up to :video MB.', ['image' => intdiv((int) config('vitafolio.limits.image_kb'), 1024), 'video' => intdiv((int) config('vitafolio.limits.video_kb'), 1024)]));
        }
        if (! $video && ! @getimagesize($file->getRealPath())) {
            $fail(__('That image could not be read. Try a JPG or PNG.'));
        }
        if (! $request->user()->canStore($file->getSize(), (int) $project?->media_size)) {
            $fail(CvDocumentController::fullMessage());
        }
    }

    private function attachMedia(Request $request, Project $project, string $bag = 'default'): void
    {
        if (! $request->hasFile('media')) {
            return;
        }
        $this->checkMedia($request, $project, $bag);
        $file = $request->file('media');
        $type = str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image';
        $uploaded = Cloudinary::upload($file, $type, 'vitafolio/projects');
        if ($uploaded === null) {
            throw ValidationException::withMessages(['media' => __('The upload failed. Please try again.')])->errorBag($bag);
        }
        // the length is only known once cloudinary has read the video, so a long one is removed again
        $maxSeconds = (int) config('vitafolio.limits.video_seconds');
        if ($type === 'video' && $uploaded['duration'] > $maxSeconds) {
            Cloudinary::destroy($uploaded['public_id'], 'video');
            throw ValidationException::withMessages(['media' => __('Videos can be up to :seconds seconds long. Trim it to the highlights and try again.', ['seconds' => $maxSeconds])])->errorBag($bag);
        }
        // the allowance was checked before the upload; a second check after it catches uploads
        // that ran at the same time and would otherwise add up past the limit
        if (! $request->user()->canStore($uploaded['bytes'], (int) $project->media_size)) {
            Cloudinary::destroy($uploaded['public_id'], $type);
            throw ValidationException::withMessages(['media' => CvDocumentController::fullMessage()])->errorBag($bag);
        }
        $this->removeMedia($project);
        $project->update([
            'media_url' => $uploaded['url'],
            'media_public_id' => $uploaded['public_id'],
            'media_type' => $type,
            'media_size' => $uploaded['bytes'] ?: $file->getSize(),
            'media_alt' => $request->input('media_alt'),
        ]);
    }

    private function removeMedia(Project $project): void
    {
        // a copied cv shares the asset, so it is only destroyed once nothing else points at it
        if ($project->media_public_id && Cloudinary::enabled() && ! Project::where('media_public_id', $project->media_public_id)->where('id', '<>', $project->id)->exists()) {
            Cloudinary::destroy($project->media_public_id, $project->media_type);
        }
        $project->update(['media_url' => null, 'media_public_id' => null, 'media_type' => null, 'media_size' => null]);
    }
}
