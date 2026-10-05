<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/** signed requests to cloudinary, so the api secret never reaches the browser */
class Cloudinary
{
    public static function enabled(): bool
    {
        return filled(config('vitafolio.cloudinary.cloud_name'))
            && filled(config('vitafolio.cloudinary.api_key'))
            && filled(config('vitafolio.cloudinary.api_secret'));
    }

    /**
     * public project media that anyone viewing the cv may load directly. images are shrunk on
     * arrival, so a huge camera original never takes up its full size in storage
     *
     * @return array{url: string, public_id: string, bytes: int, duration: float}|null
     */
    public static function upload(UploadedFile $file, string $type, string $folder): ?array
    {
        $params = ['folder' => $folder, 'timestamp' => time()];
        if ($type === 'image') {
            $params['transformation'] = 'c_limit,w_2000,h_2000';
        }
        $response = rescue(fn () => Http::timeout(90)->attach('file', fopen($file->getRealPath(), 'r'), 'upload')
            ->post(self::endpoint($type === 'video' ? 'video' : 'image', 'upload'), self::signed($params)), report: false);

        return $response?->successful() ? [
            'url' => $response->json('secure_url'),
            'public_id' => $response->json('public_id'),
            'bytes' => (int) $response->json('bytes'),
            'duration' => (float) $response->json('duration', 0),
        ] : null;
    }

    /**
     * a cv file, stored as an authenticated raw asset: its address cannot be guessed or opened
     * without a signature, so a private cv's file stays private. returns the public id
     */
    public static function storeFile(string $bytes, string $folder, string $extension): ?string
    {
        $params = ['folder' => $folder, 'timestamp' => time(), 'type' => 'authenticated'];
        $response = rescue(fn () => Http::timeout(60)->attach('file', $bytes, 'file.'.$extension)
            ->post(self::endpoint('raw', 'upload'), self::signed($params)), report: false);

        return $response?->successful() ? $response->json('public_id') : null;
    }

    /** fetches an authenticated file through the signed download api */
    public static function fetchFile(string $publicId): ?string
    {
        $params = ['public_id' => $publicId, 'timestamp' => time(), 'type' => 'authenticated', 'expires_at' => time() + 300];
        $response = rescue(fn () => Http::timeout(30)->get(self::endpoint('raw', 'download'), self::signed($params)), report: false);

        return $response?->successful() ? $response->body() : null;
    }

    /** $type is image, video or file; failures are ignored because an orphaned asset is harmless */
    public static function destroy(string $publicId, string $type): void
    {
        $params = ['public_id' => $publicId, 'timestamp' => time()];
        $resource = match ($type) {
            'video' => 'video',
            'file' => 'raw',
            default => 'image',
        };
        if ($type === 'file') {
            $params['type'] = 'authenticated';
        }
        rescue(fn () => Http::asForm()->timeout(20)->post(self::endpoint($resource, 'destroy'), self::signed($params)), report: false);
    }

    /** delivery url that picks the best format and quality for each browser and caps the width */
    public static function delivery(string $url, string $type): string
    {
        $transform = $type === 'video' ? 'q_auto,vc_auto,w_1280' : 'f_auto,q_auto,w_1200';

        return str_replace('/upload/', '/upload/'.$transform.'/', $url);
    }

    private static function endpoint(string $resource, string $action): string
    {
        return 'https://api.cloudinary.com/v1_1/'.config('vitafolio.cloudinary.cloud_name').'/'.$resource.'/'.$action;
    }

    /** every parameter except the key and the signature itself is signed, sorted by name */
    private static function signed(array $params): array
    {
        ksort($params);
        $signature = sha1(urldecode(http_build_query($params)).config('vitafolio.cloudinary.api_secret'));

        return $params + ['api_key' => config('vitafolio.cloudinary.api_key'), 'signature' => $signature];
    }
}
