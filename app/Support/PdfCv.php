<?php

namespace App\Support;

use App\Models\Cv;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;

/**
 * builds the cv and cover letter pdfs. typst renders them as tagged pdf/ua-1 files: headings,
 * paragraphs, lists, tables, links and the photo carry structure tags in reading order, with the
 * document's language, its title and bookmarks from the headings. mpdf cannot write structure
 * tags, so it is kept only as a fallback for a machine without the typst binary
 */
class PdfCv
{
    public static function build(Cv $cv, bool $letter): string
    {
        $picture = self::picture($cv);
        $binary = self::binary();
        if ($binary === null) {
            Log::warning('typst is not installed, so the pdf is made without structure tags');

            return self::mpdf($cv, $letter, $picture);
        }

        return self::typst($binary, $cv, $letter, $picture);
    }

    /** the typst executable when it can be found, otherwise null */
    public static function binary(): ?string
    {
        $configured = (string) config('vitafolio.typst_binary', 'typst');
        if (str_contains($configured, DIRECTORY_SEPARATOR)) {
            return is_executable($configured) ? $configured : null;
        }

        return (new ExecutableFinder)->find($configured);
    }

    /** the content as plain data for the typst layout, which places every string as text and never as markup */
    public static function data(Cv $cv, bool $letter, bool $photo): array
    {
        $user = $cv->user;
        $sections = [];
        if ($letter) {
            $sections[] = ['kind' => 'text', 'heading' => $cv->label('Cover letter'), 'note' => filled($cv->letter_to) ? (string) $cv->letter_to : null, 'blocks' => self::blocks($cv->cover_letter)];
        } else {
            foreach ($cv->orderedSections() as $section) {
                $text = ['profile' => $cv->label('Profile'), 'experience' => $cv->label('Experience'), 'education' => $cv->label('Education')];
                if (isset($text[$section]) && filled($cv->{$section})) {
                    $sections[] = ['kind' => 'text', 'heading' => $text[$section], 'blocks' => self::blocks($cv->{$section})];
                } elseif ($section === 'projects' && $cv->projects->isNotEmpty()) {
                    $sections[] = ['kind' => 'projects', 'heading' => $cv->label('Projects'), 'projects' => $cv->projects->map(fn ($project) => [
                        'title' => (string) $project->title,
                        'url' => $project->url ?: null,
                        'blocks' => self::blocks($project->description),
                    ])->values()->all()];
                } elseif ($section === 'skills' && $cv->tags->isNotEmpty()) {
                    $sections[] = ['kind' => 'skills', 'heading' => $cv->label('Skills'), 'items' => $cv->tags->pluck('name')->map(fn ($name) => (string) $name)->values()->all()];
                } elseif ($section === 'links' && ($links = Links::parse($user->links)) !== []) {
                    $sections[] = ['kind' => 'links', 'heading' => $cv->label('Links'), 'links' => array_map(fn ($link) => ['label' => $link['label'], 'url' => $link['url']], $links)];
                }
            }
        }

        $locale = $cv->documentLocale();
        [$lang, $region] = array_pad(explode('-', strtolower(Locales::html($locale))), 2, null);

        return [
            'title' => self::title($cv, $letter),
            // the pdf's language and reading direction follow the cv's document language
            'lang' => $lang,
            'region' => $region,
            'dir' => Locales::dir($locale),
            'script_fonts' => self::scriptFonts($locale),
            'labels' => [
                'page' => $cv->label('page :current of :total'),
                'site' => $cv->label('Site'),
                'address' => $cv->label('Address'),
                'photo' => $cv->label('Photo of :name', ['name' => (string) $user->name]),
            ],
            'name' => (string) $user->name,
            'keywords' => [$letter ? $cv->label('Cover letter') : $cv->label('CV')],
            'accent' => config('vitafolio.accents')[$cv->accent]['hex'] ?? '#14213d',
            'theme' => (string) $cv->theme,
            'font' => ['sans' => 'DejaVu Sans', 'serif' => 'DejaVu Serif', 'mono' => 'DejaVu Sans Mono'][$cv->font] ?? 'DejaVu Sans',
            'address' => $letter ? route('cv.letter', $cv) : route('cv.show', $cv),
            'headline' => $cv->displayHeadline() ?: null,
            'details' => array_values(array_map('strval', array_filter([
                $user->pronouns,
                $user->location,
                $user->university,
                $cv->key_language ? $cv->label('Main language: :language', ['language' => $cv->key_language]) : null,
                $user->availability !== 'none' ? $cv->label('Looking for: :availability', ['availability' => $cv->label(config('vitafolio.availability')[$user->availability] ?? '')]) : null,
                $cv->show_email ? $user->email : null,
            ]))),
            'photo' => $photo ? 'photo.png' : null,
            'sections' => $sections,
        ];
    }

    public static function title(Cv $cv, bool $letter): string
    {
        return $letter ? $cv->label(':name cover letter', ['name' => $cv->user->name]) : $cv->label(':name CV', ['name' => $cv->user->name]);
    }

    /**
     * fonts for text outside latin, greek and cyrillic, in the order typst tries them. urdu reads in
     * nastaliq and arabic in naskh; chinese and any other script fall through to the later entries
     */
    public static function scriptFonts(string $locale): array
    {
        return $locale === 'ur'
            ? ['Noto Nastaliq Urdu', 'Noto Naskh Arabic', 'Noto Sans SC']
            : ['Noto Naskh Arabic', 'Noto Nastaliq Urdu', 'Noto Sans SC'];
    }

    /**
     * the folders typst reads fonts from: dejavu ships with mpdf, the arabic and urdu faces are in
     * the repository and the chinese one is fetched into the same folder by scripts/fetch-pdf-fonts.sh
     */
    public static function fontPaths(): array
    {
        return [base_path('vendor/mpdf/mpdf/ttfonts'), resource_path('pdf/fonts')];
    }

    /**
     * plain cv text, read the same way as the word export: blank lines separate paragraphs and
     * lines starting "- " become list items, so they are tagged as a real list in the pdf
     */
    public static function blocks(?string $text): array
    {
        $blocks = [];
        $current = null;
        foreach (preg_split('/\R/u', trim((string) $text)) ?: [] as $line) {
            $line = trim($line);
            $type = $line === '' ? null : (str_starts_with($line, '- ') ? 'list' : 'p');
            if ($type !== ($current['type'] ?? null) && $current !== null) {
                $blocks[] = $current;
                $current = null;
            }
            if ($type === 'list') {
                $current ??= ['type' => 'list', 'items' => []];
                $current['items'][] = trim(substr($line, 2));
            } elseif ($type === 'p') {
                $current ??= ['type' => 'p', 'lines' => []];
                $current['lines'][] = $line;
            }
        }
        if ($current !== null) {
            $blocks[] = $current;
        }

        return $blocks;
    }

    /** the round photo as png bytes. null when the owner has none or it cannot be read */
    private static function picture(Cv $cv): ?string
    {
        if (! $cv->user->hasAvatar()) {
            return null;
        }

        return Images::circlePng((string) DB::table('users')->where('id', $cv->user_id)->value('avatar'), 300);
    }

    private static function typst(string $binary, Cv $cv, bool $letter, ?string $picture): string
    {
        // each render gets its own folder, which typst treats as its root, so it can read nothing else
        $dir = storage_path('framework/cache/typst/'.Str::random(24));
        File::ensureDirectoryExists($dir);

        try {
            File::copy(resource_path('pdf/cv.typ'), $dir.'/main.typ');
            File::put($dir.'/data.json', json_encode(self::data($cv, $letter, $picture !== null), JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE));
            if ($picture !== null) {
                File::put($dir.'/photo.png', $picture);
            }

            // only the fonts listed in fontPaths are used, so the pdf looks the same on every machine;
            // system fonts are ignored for the same reason. pdf/ua-1 makes typst refuse to write a
            // file that would break the standard, rather than quietly producing an inaccessible one
            $result = Process::path($dir)->timeout(30)->run([
                $binary, 'compile',
                '--root', $dir,
                '--ignore-system-fonts',
                ...collect(self::fontPaths())->flatMap(fn (string $path) => ['--font-path', $path])->all(),
                '--pdf-standard', 'ua-1',
                // the cv's own last change, so the same cv always gives the same file
                ...($cv->updated_at ? ['--creation-timestamp', (string) $cv->updated_at->getTimestamp()] : []),
                'main.typ', 'out.pdf',
            ]);
            if (! $result->successful() || ! is_file($dir.'/out.pdf')) {
                throw new RuntimeException('typst could not render the pdf: '.Str::limit(trim($result->errorOutput()), 500));
            }

            return (string) file_get_contents($dir.'/out.pdf');
        } finally {
            File::deleteDirectory($dir);
        }
    }

    private static function mpdf(Cv $cv, bool $letter, ?string $picture): string
    {
        $html = view('cv.pdf', [
            'cv' => $cv,
            'links' => Links::parse($cv->user->links),
            // the photo is embedded as data so mpdf never fetches anything over the network
            'picture' => $picture ? 'data:image/png;base64,'.base64_encode($picture) : null,
            'letter' => $letter,
        ])->render();

        $mpdf = new Mpdf([
            'tempDir' => storage_path('framework/cache/mpdf'),
            'margin_top' => 14, 'margin_bottom' => 14, 'margin_left' => 16, 'margin_right' => 16,
            'default_font' => 'dejavusans',
        ]);
        $mpdf->SetTitle(self::title($cv, $letter));
        $mpdf->WriteHTML($html);

        return $mpdf->OutputBinaryData();
    }
}
