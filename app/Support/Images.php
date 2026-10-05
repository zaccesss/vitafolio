<?php

namespace App\Support;

use GdImage;

class Images
{
    /**
     * re-encodes an upload as a square jpeg. this strips location and camera data, drops anything
     * hidden inside the file and caps the size, so the stored copy is always small and clean.
     *
     * $crop is [x, y, side] as fractions of the upright image's width, height and width, the way
     * the cropper in the browser measured it. without one the centre square is used
     */
    public static function squareJpeg(string $path, int $size, ?array $crop = null): ?string
    {
        $bytes = (string) file_get_contents($path);
        $source = @imagecreatefromstring($bytes);
        if ($source === false) {
            return null;
        }
        $source = self::upright($source, self::jpegOrientation($bytes));
        $w = imagesx($source);
        $h = imagesy($source);

        $side = min($w, $h);
        $x = intdiv($w - $side, 2);
        $y = intdiv($h - $side, 2);
        if ($crop !== null) {
            // clamp everything, so a tampered request can only ever pick a square inside the photo
            $side = (int) max(16, min($w, $h, round($crop[2] * $w)));
            $x = (int) max(0, min($w - $side, round($crop[0] * $w)));
            $y = (int) max(0, min($h - $side, round($crop[1] * $h)));
        }

        $square = imagecreatetruecolor($size, $size);
        // a white base so transparent pngs do not turn black
        imagefill($square, 0, 0, imagecolorallocate($square, 255, 255, 255));
        imagecopyresampled($square, $source, 0, 0, $x, $y, $size, $size, $side, $side);
        ob_start();
        imagejpeg($square, null, 86);

        return (string) ob_get_clean();
    }

    /** a round copy with transparent corners, for places that cannot clip an image to a circle, such as mpdf */
    public static function circlePng(string $bytes, int $size): ?string
    {
        $source = @imagecreatefromstring($bytes);
        if ($source === false) {
            return null;
        }
        // drawn at twice the size and scaled down, which smooths the edge of the circle
        $big = $size * 2;
        $canvas = imagecreatetruecolor($big, $big);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $clear = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $clear);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $big, $big, imagesx($source), imagesy($source));
        $r = $big / 2;
        for ($y = 0; $y < $big; $y++) {
            for ($x = 0; $x < $big; $x++) {
                if (($x - $r + 0.5) ** 2 + ($y - $r + 0.5) ** 2 > $r * $r) {
                    imagesetpixel($canvas, $x, $y, $clear);
                }
            }
        }
        $out = imagecreatetruecolor($size, $size);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagecopyresampled($out, $canvas, 0, 0, 0, 0, $size, $size, $big, $big);
        ob_start();
        imagepng($out, null, 6);

        return (string) ob_get_clean();
    }

    /**
     * phone cameras save photos sideways and record the turn in an exif tag. browsers apply it but
     * gd does not, so without this the stored photo and the cropper preview would disagree
     */
    private static function upright(GdImage $img, int $orientation): GdImage
    {
        // imagerotate turns anticlockwise; 5 and 7 are the mirrored versions of 8 and 6
        $turned = match ($orientation) {
            3 => imagerotate($img, 180, 0),
            6, 7 => imagerotate($img, -90, 0),
            5, 8 => imagerotate($img, 90, 0),
            default => $img,
        };
        $turned = $turned === false ? $img : $turned;
        match ($orientation) {
            2 => imageflip($turned, IMG_FLIP_HORIZONTAL),
            4, 5, 7 => imageflip($turned, IMG_FLIP_VERTICAL),
            default => null,
        };

        return $turned;
    }

    /** reads the exif orientation tag straight from the jpeg segments, so the exif extension is not needed */
    public static function jpegOrientation(string $bytes): int
    {
        $len = strlen($bytes);
        if ($len < 4 || substr($bytes, 0, 2) !== "\xFF\xD8") {
            return 1;
        }
        for ($i = 2; $i + 4 <= $len && $bytes[$i] === "\xFF";) {
            $marker = ord($bytes[$i + 1]);
            $segment = unpack('n', substr($bytes, $i + 2, 2))[1];
            if ($marker === 0xDA) {
                break;
            }
            if ($marker === 0xE1 && substr($bytes, $i + 4, 6) === "Exif\0\0") {
                return self::tiffOrientation(substr($bytes, $i + 10, max(0, $segment - 8)));
            }
            $i += 2 + $segment;
        }

        return 1;
    }

    private static function tiffOrientation(string $tiff): int
    {
        $len = strlen($tiff);
        if ($len < 8) {
            return 1;
        }
        $little = substr($tiff, 0, 2) === 'II';
        $u16 = fn (int $at): int => $at + 2 <= $len ? unpack($little ? 'v' : 'n', substr($tiff, $at, 2))[1] : 0;
        $u32 = fn (int $at): int => $at + 4 <= $len ? unpack($little ? 'V' : 'N', substr($tiff, $at, 4))[1] : 0;
        $ifd = $u32(4);
        $count = $u16($ifd);
        for ($n = 0; $n < $count; $n++) {
            $entry = $ifd + 2 + $n * 12;
            if ($entry + 12 > $len) {
                break;
            }
            if ($u16($entry) === 0x0112) {
                $value = $u16($entry + 8);

                return $value >= 1 && $value <= 8 ? $value : 1;
            }
        }

        return 1;
    }
}
