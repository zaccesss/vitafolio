<?php

namespace App\Support;

use App\Models\Cv;
use App\Models\CvDocument;
use Illuminate\Support\Facades\DB;

/**
 * where cv files live. cloudinary when it is set up, so the database stays small; the database
 * otherwise, so a copy of the site with no cloudinary account still works
 */
class DocumentStore
{
    /** stores a file as the cv's only document, replacing and cleaning up any earlier one */
    public static function put(Cv $cv, string $bytes, string $filename, string $mime, string $source): bool
    {
        $previous = CvDocument::where('cv_id', $cv->id)->first(['id', 'storage', 'public_id']);
        $attributes = ['filename' => $filename, 'mime' => $mime, 'size' => strlen($bytes), 'source' => $source];

        if (Cloudinary::enabled()) {
            $publicId = Cloudinary::storeFile($bytes, 'vitafolio/cv-files', pathinfo($filename, PATHINFO_EXTENSION) ?: 'pdf');
            if ($publicId === null) {
                return false;
            }
            $attributes += ['storage' => 'cloudinary', 'public_id' => $publicId, 'data' => null];
        } else {
            $attributes += ['storage' => 'database', 'public_id' => null, 'data' => $bytes];
        }

        CvDocument::updateOrCreate(['cv_id' => $cv->id], $attributes);
        if ($previous?->storage === 'cloudinary' && $previous->public_id) {
            self::forgetRemote($previous->public_id);
        }
        $cv->touch();

        return true;
    }

    public static function read(CvDocument $document): ?string
    {
        return $document->storage === 'cloudinary'
            ? Cloudinary::fetchFile((string) $document->public_id)
            : DB::table('cv_documents')->where('id', $document->id)->value('data');
    }

    public static function delete(Cv $cv): void
    {
        $document = CvDocument::where('cv_id', $cv->id)->first(['id', 'storage', 'public_id']);
        if (! $document) {
            return;
        }
        $document->delete();
        if ($document->storage === 'cloudinary' && $document->public_id) {
            self::forgetRemote($document->public_id);
        }
    }

    /** deleted after the response, so removing a cv or an account is never slowed by the network */
    private static function forgetRemote(string $publicId): void
    {
        defer(fn () => Cloudinary::destroy($publicId, 'file'));
    }
}
