<?php

namespace Tests\Unit;

use App\Support\Images;
use PHPUnit\Framework\TestCase;

class ImagesTest extends TestCase
{
    /** a jpeg of the given size with an exif orientation tag, as a phone camera writes it */
    private function jpeg(int $w, int $h, int $orientation): string
    {
        $img = imagecreatetruecolor($w, $h);
        imagefilledrectangle($img, 0, 0, intdiv($w, 2), $h, imagecolorallocate($img, 255, 0, 0));
        ob_start();
        imagejpeg($img);
        $bytes = (string) ob_get_clean();
        $tiff = 'II'.pack('v', 42).pack('V', 8).pack('v', 1).pack('vvVvv', 0x0112, 3, 1, $orientation, 0).pack('V', 0);
        $app1 = "Exif\0\0".$tiff;

        return "\xFF\xD8\xFF\xE1".pack('n', strlen($app1) + 2).$app1.substr($bytes, 2);
    }

    public function test_the_exif_orientation_is_read(): void
    {
        $this->assertSame(6, Images::jpegOrientation($this->jpeg(40, 20, 6)));
        $this->assertSame(1, Images::jpegOrientation('not an image'));
    }

    public function test_photos_are_turned_upright_before_cropping(): void
    {
        // red on the left half; orientation 6 turns it clockwise, so red ends up on top and the
        // centre square holds the bottom of the red half above the bottom of the dark half
        $path = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($path, $this->jpeg(200, 100, 6));
        $square = imagecreatefromstring((string) Images::squareJpeg($path, 100));
        unlink($path);

        $top = imagecolorsforindex($square, imagecolorat($square, 50, 10));
        $bottom = imagecolorsforindex($square, imagecolorat($square, 50, 90));
        $this->assertGreaterThan(200, $top['red']);
        $this->assertLessThan(60, $bottom['red']);
    }

    public function test_a_tampered_crop_stays_inside_the_photo(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($path, $this->jpeg(100, 100, 1));
        $bytes = Images::squareJpeg($path, 50, [5, -3, 9]);
        unlink($path);

        $this->assertNotNull($bytes);
        $this->assertSame([50, 50], array_slice(getimagesizefromstring($bytes), 0, 2));
    }

    public function test_round_photos_have_transparent_corners(): void
    {
        $img = imagecreatetruecolor(60, 60);
        ob_start();
        imagejpeg($img);
        $round = imagecreatefromstring((string) Images::circlePng((string) ob_get_clean(), 60));

        $this->assertSame(127, imagecolorsforindex($round, imagecolorat($round, 0, 0))['alpha']);
        $this->assertSame(0, imagecolorsforindex($round, imagecolorat($round, 30, 30))['alpha']);
    }
}
