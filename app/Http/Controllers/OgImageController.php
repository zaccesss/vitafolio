<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use GdImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** link preview image for a cv, so shared links show the photo, name, headline and skills */
class OgImageController extends Controller
{
    private const W = 1200;

    private const H = 630;

    private const PHOTO = 260;

    public function __invoke(Request $request, Cv $cv): Response
    {
        abort_unless($cv->isVisibleTo($request->user()), 404);
        $cv->load(['user', 'tags']);

        $img = imagecreatetruecolor(self::W, self::H);
        imagealphablending($img, true);
        $midnight = imagecolorallocate($img, 0x14, 0x21, 0x3D);
        $band = imagecolorallocate($img, 0x0B, 0x12, 0x20);
        $white = imagecolorallocate($img, 0xFF, 0xFF, 0xFF);
        $soft = imagecolorallocate($img, 0xD6, 0xDD, 0xEA);
        $brass = imagecolorallocate($img, 0xE8, 0xC9, 0x8F);
        imagefilledrectangle($img, 0, 0, self::W, self::H, $midnight);
        imagefilledrectangle($img, 0, self::H - 110, self::W, self::H, $band);
        imagefilledrectangle($img, 0, self::H - 114, self::W, self::H - 111, $brass);

        // the dejavu fonts ship with mpdf, so no extra font files are needed
        $regular = base_path('vendor/mpdf/mpdf/ttfonts/DejaVuSans.ttf');
        $bold = base_path('vendor/mpdf/mpdf/ttfonts/DejaVuSans-Bold.ttf');

        // a cv the requester can open shows its owner's photo on the page too, so the preview may
        $hasPhoto = $cv->user->hasAvatar() && $this->drawPhoto($img, $cv->user->id, $midnight, $brass);
        $textWidth = $hasPhoto ? 700 : 1040;

        $y = 150;
        foreach ($this->wrap($cv->user->name, $bold, 60, $textWidth) as $line) {
            imagettftext($img, 60, 0, 80, $y, $white, $bold, $line);
            $y += 82;
        }
        if ($cv->displayHeadline()) {
            foreach ($this->wrap($cv->displayHeadline(), $regular, 30, $textWidth) as $line) {
                imagettftext($img, 30, 0, 80, $y + 6, $soft, $regular, $line);
                $y += 48;
            }
        }
        $skills = $cv->tags->take(5)->pluck('name')->implode('  ·  ');
        if ($skills !== '') {
            imagettftext($img, 24, 0, 80, min($y + 64, self::H - 160), $brass, $regular, Str::limit($skills, $hasPhoto ? 48 : 70));
        }

        $this->drawMark($img, 80, self::H - 82, $white, $brass, $band);
        imagettftext($img, 30, 0, 136, self::H - 42, $white, $bold, config('app.name'));
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: '';
        $box = imagettfbbox(24, 0, $regular, $host);
        imagettftext($img, 24, 0, self::W - 80 - ($box[2] - $box[0]), self::H - 44, $soft, $regular, $host);

        ob_start();
        imagepng($img, null, 6);

        return response((string) ob_get_clean(), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => $cv->visibility === 'public' && ! $cv->hidden_at ? 'public, max-age=3600' : 'private, no-store',
        ]);
    }

    /** the photo in a brass ring on the right; gd has no clip paths, so a frame with a round hole goes on top */
    private function drawPhoto(GdImage $img, int $userId, int $background, int $ring): bool
    {
        $photo = @imagecreatefromstring((string) DB::table('users')->where('id', $userId)->value('avatar'));
        if ($photo === false) {
            return false;
        }
        $size = self::PHOTO;
        $x = self::W - 80 - $size;
        $y = 90;
        imagecopyresampled($img, $photo, $x, $y, 0, 0, $size, $size, imagesx($photo), imagesy($photo));

        $frame = imagecreatetruecolor($size, $size);
        imagealphablending($frame, false);
        imagesavealpha($frame, true);
        [$r, $g, $b] = [($background >> 16) & 0xFF, ($background >> 8) & 0xFF, $background & 0xFF];
        imagefill($frame, 0, 0, imagecolorallocatealpha($frame, $r, $g, $b, 0));
        imagefilledellipse($frame, intdiv($size, 2), intdiv($size, 2), $size, $size, imagecolorallocatealpha($frame, 0, 0, 0, 127));
        imagecopy($img, $frame, $x, $y, 0, 0, $size, $size);

        // a few nested ellipses make a ring thick enough to hide the hole's stepped edge
        for ($i = 0; $i < 6; $i++) {
            imageellipse($img, $x + intdiv($size, 2), $y + intdiv($size, 2), $size - 2 + $i, $size - 2 + $i, $ring);
        }

        return true;
    }

    /** the vitafolio mark: a page with a folded brass corner and a tick, as in favicon.svg */
    private function drawMark(GdImage $img, int $x, int $y, int $page, int $fold, int $tick): void
    {
        $s = 44 / 34;
        $p = fn (float $px, float $py) => [(int) round($x + ($px - 14) * $s), (int) round($y + ($py - 16) * $s)];
        $points = fn (array ...$pairs) => array_merge(...array_map(fn ($pair) => $p(...$pair), $pairs));
        imagefilledpolygon($img, $points([14, 16], [36, 16], [48, 28], [48, 54], [14, 54]), $page);
        imagefilledpolygon($img, $points([36, 16], [48, 28], [36, 28]), $fold);
        imagesetthickness($img, 5);
        imageline($img, ...[...$p(20, 34), ...$p(28, 46), $tick]);
        imageline($img, ...[...$p(28, 46), ...$p(36, 34), $tick]);
        imagesetthickness($img, 1);
    }

    /** splits text into at most two lines that fit the given pixel width */
    private function wrap(string $text, string $font, int $size, int $maxWidth): array
    {
        $lines = [];
        $line = '';
        foreach (preg_split('/\s+/', trim($text)) ?: [] as $word) {
            $try = $line === '' ? $word : $line.' '.$word;
            $box = imagettfbbox($size, 0, $font, $try);
            if ($maxWidth < $box[2] - $box[0] && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $try;
            }
        }

        return array_slice(array_filter([...$lines, $line]), 0, 2);
    }
}
