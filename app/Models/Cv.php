<?php

namespace App\Models;

use App\Support\Cloudinary;
use App\Support\DocumentStore;
use App\Support\IndexNow;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/** one version of someone's cv; who they are lives on the user's profile */
class Cv extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'headline', 'key_language', 'profile', 'education', 'experience',
        'visibility', 'show_email', 'theme', 'accent', 'font', 'latex_source', 'section_order',
        'letter_to', 'cover_letter', 'language',
    ];

    protected function casts(): array
    {
        return [
            'show_email' => 'boolean',
            'view_count' => 'integer',
            'hidden_at' => 'datetime',
            'section_order' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** search engines hear about public cv changes, including one that just went private or was deleted */
    protected static function booted(): void
    {
        // a new cv starts in the language its owner is using the site in
        static::creating(function (Cv $cv) {
            if (! Locales::supported($cv->language)) {
                $cv->language = Locales::supported(app()->getLocale()) ? app()->getLocale() : Locales::DEFAULT;
            }
        });
        static::saved(function (Cv $cv) {
            $fields = ['title', 'slug', 'headline', 'key_language', 'profile', 'education', 'experience', 'section_order', 'visibility', 'hidden_at', 'theme'];
            $wasPublic = $cv->getOriginal('visibility') === 'public' && $cv->getOriginal('hidden_at') === null;
            $isPublic = $cv->visibility === 'public' && $cv->hidden_at === null;
            if (! ($cv->wasRecentlyCreated || $cv->wasChanged($fields)) || ! ($wasPublic || $isPublic)) {
                return;
            }
            $urls = [route('cv.show', $cv->slug)];
            if ($cv->wasChanged('slug') && $cv->getOriginal('slug')) {
                $urls[] = route('cv.show', $cv->getOriginal('slug'));
            }
            IndexNow::submit(...$urls);
        });
        // the database removes a cv's file row and projects on its own, but not their copies on
        // cloudinary, so those are cleaned up here before the rows disappear
        static::deleting(function (Cv $cv) {
            DocumentStore::delete($cv);
            $own = $cv->projects()->whereNotNull('media_public_id')->get(['id', 'media_public_id', 'media_type']);
            // a copied cv shares its assets, so only those nothing else points at are destroyed
            $media = $own->reject(fn ($p) => Project::where('media_public_id', $p->media_public_id)->whereNotIn('id', $own->pluck('id'))->exists());
            if ($media->isNotEmpty() && Cloudinary::enabled()) {
                defer(fn () => $media->each(fn ($p) => Cloudinary::destroy($p->media_public_id, $p->media_type)));
            }
        });
        static::deleted(function (Cv $cv) {
            if ($cv->visibility === 'public') {
                IndexNow::submit(route('cv.show', $cv->slug));
            }
        });
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->orderBy('name');
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class)->orderBy('position');
    }

    /**
     * metadata only; the file bytes are read by the download route alone
     *
     * @return HasOne<CvDocument, $this>
     */
    public function document(): HasOne
    {
        return $this->hasOne(CvDocument::class)->select(['id', 'cv_id', 'filename', 'mime', 'size', 'source', 'updated_at']);
    }

    /** @return HasMany<CvView, $this> */
    public function views(): HasMany
    {
        return $this->hasMany(CvView::class);
    }

    /** @return HasMany<Report, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /** @return HasMany<Endorsement, $this> */
    public function endorsements(): HasMany
    {
        return $this->hasMany(Endorsement::class)->latest();
    }

    /** listed in the directory: public and not hidden, with the owner verified, not suspended and not private */
    public function scopeListed(Builder $query): Builder
    {
        return $query->where('cvs.visibility', 'public')->whereNull('cvs.hidden_at')
            ->whereHas('user', fn (Builder $q) => $q->whereNotNull('email_verified_at')
                ->whereNull('suspended_at')->where('profile_visibility', '<>', 'private'));
    }

    /**
     * owners always see their own cvs and admins see everything. anyone else needs a cv that
     * is not private, not hidden by a moderator and not owned by a suspended account
     */
    public function isVisibleTo(?User $viewer): bool
    {
        if ($viewer && ($viewer->id === $this->user_id || $viewer->isAdmin())) {
            return true;
        }

        return $this->visibility !== 'private' && $this->hidden_at === null && ! $this->user->isSuspended();
    }

    /** an empty letter means the cv has none, so its page and downloads answer 404 */
    public function hasCoverLetter(): bool
    {
        return filled($this->cover_letter);
    }

    /** a cv can override the profile headline, for example to target a particular role */
    public function displayHeadline(): ?string
    {
        return $this->headline ?: $this->user->headline;
    }

    /** sections in the owner's chosen order; anything missing from a saved order goes at the end */
    /** the language code of the cv's own labels and downloads */
    public function documentLocale(): string
    {
        return Locales::supported($this->language) ? $this->language : Locales::DEFAULT;
    }

    /** a fixed cv label in the cv's own language, whatever language the reader uses the site in */
    public function label(string $key, array $replace = []): string
    {
        return __($key, $replace, $this->documentLocale());
    }

    public function orderedSections(): array
    {
        $all = ['profile', 'experience', 'projects', 'education', 'skills', 'links'];
        $saved = array_values(array_intersect($this->section_order ?? [], $all));

        return array_values(array_unique([...$saved, ...$all]));
    }

    /** filled sections, used for the completeness checklist */
    public function completeness(): array
    {
        return [
            'Headline' => filled($this->displayHeadline()),
            'Summary' => filled($this->profile),
            'Skills' => $this->tags->isNotEmpty(),
            'Experience' => filled($this->experience),
            'Projects' => $this->projects->isNotEmpty(),
            'Education' => filled($this->education),
            'Profile photo' => $this->user->hasAvatar(),
            'Uploaded or LaTeX file' => $this->document !== null,
        ];
    }

    public static function uniqueSlug(string $text, ?int $exceptId = null): string
    {
        $base = Str::limit(Str::slug($text) ?: 'cv', 90, '');
        // words used by other /cv/ routes can never become a cv address
        if (in_array($base, ['edit', 'new', 'export'], true)) {
            $base .= '-cv';
        }
        $slug = $base;
        for ($n = 2; static::where('slug', $slug)->when($exceptId, fn ($q) => $q->where('id', '<>', $exceptId))->exists(); $n++) {
            $slug = $base.'-'.$n;
        }

        return $slug;
    }
}
