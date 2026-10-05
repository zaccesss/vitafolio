<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $str = fn (string $key) => is_string($request->input($key)) ? trim($request->input($key)) : '';
        $q = $str('q');
        $selectedTags = array_values(array_filter((array) $request->input('tags', []), 'is_string'));
        $availability = $str('availability');
        $university = $str('university');
        $sort = in_array($request->input('sort'), ['updated', 'name', 'views'], true) ? $request->input('sort') : 'updated';

        // wildcards typed by a visitor are matched literally rather than as patterns
        $like = '%'.addcslashes($q, '%_\\').'%';

        $cvs = Cv::listed()
            ->with(['user:id,name,handle,headline,university,availability,avatar_version', 'tags'])
            ->when($q !== '', fn (Builder $query) => $query->where(fn (Builder $w) => $w
                ->whereHas('user', fn (Builder $u) => $u->where('name', 'like', $like))
                ->orWhere('cvs.headline', 'like', $like)
                ->orWhereHas('user', fn (Builder $u) => $u->where('headline', 'like', $like))
                ->orWhere('key_language', 'like', $like)
                ->orWhereHas('tags', fn (Builder $t) => $t->where('name', 'like', $like))))
            ->when($selectedTags !== [], function (Builder $query) use ($selectedTags) {
                foreach (array_slice($selectedTags, 0, 5) as $slug) {
                    $query->whereHas('tags', fn (Builder $t) => $t->where('slug', $slug));
                }
            })
            ->when(array_key_exists($availability, config('vitafolio.availability')) && $availability !== 'none',
                fn (Builder $query) => $query->whereHas('user', fn (Builder $u) => $u->where('availability', $availability)))
            ->when($university !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $u) => $u->where('university', 'like', '%'.addcslashes($university, '%_\\').'%')))
            ->when($sort === 'name', fn (Builder $query) => $query->orderBy(
                User::select('name')->whereColumn('users.id', 'cvs.user_id')))
            ->when($sort === 'views', fn (Builder $query) => $query->orderByDesc('view_count'))
            ->when($sort === 'updated', fn (Builder $query) => $query->latest('updated_at'))
            ->paginate(config('vitafolio.per_page'))
            ->withQueryString();

        $popularTags = Tag::query()
            ->whereHas('cvs', fn ($c) => $c->listed())
            ->withCount(['cvs' => fn ($c) => $c->listed()])
            ->orderByDesc('cvs_count')->orderBy('name')->limit(24)->get();

        // universities with at least one listed cv, for the filter list
        $universities = User::whereNotNull('university')->whereNull('suspended_at')
            ->whereHas('cvs', fn (Builder $c) => $c->listed())->distinct()->orderBy('university')->pluck('university');

        return view('directory.index', compact('cvs', 'q', 'selectedTags', 'availability', 'university', 'sort', 'popularTags', 'universities'));
    }
}
