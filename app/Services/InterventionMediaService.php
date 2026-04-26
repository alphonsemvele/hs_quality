<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Intervention;
use App\Models\InterventionPhoto;
use App\Models\InterventionSignature;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InterventionMediaService
{
    private const PHOTO_ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    private const PHOTO_MAX_BYTES = 5 * 1024 * 1024; // 5 MB

    /** Wave 1 / H8 — decoded signature payload must not exceed 200 KB. */
    private const SIGNATURE_MAX_DECODED_BYTES = 200 * 1024;

    private const SIGNED_URL_TTL = 3600; // 1 hour

    public function storePhoto(Intervention $intervention, UploadedFile $file, User $uploader): InterventionPhoto
    {
        if (! in_array($file->getMimeType(), self::PHOTO_ALLOWED_MIMES, true)) {
            throw new HttpException(422, 'Type de fichier non autorisé.');
        }

        if ($file->getSize() > self::PHOTO_MAX_BYTES) {
            throw new HttpException(422, 'La photo ne doit pas dépasser 5 Mo.');
        }

        // Wave 1 / H8 — re-encode through Intervention Image. This strips
        // EXIF metadata, color-profile chunks, and any embedded payloads
        // (PHP-in-EXIF, polyglot files, SVG-as-PNG via header tricks).
        // Output is always a clean JPEG; the original bytes never reach S3.
        // v4 API: decodePath() returns an Image; encoder objects (not
        // toJpeg/toString helpers) produce the binary bytes.
        $reencoded = (string) (new ImageManager(new GdDriver))
            ->decodePath($file->getRealPath())
            ->encode(new JpegEncoder(quality: 85));

        $uuid = (string) Str::uuid();
        $path = "structures/{$intervention->structure_id}/interventions/{$intervention->id}/photos/{$uuid}.jpg";

        Storage::disk('s3')->put($path, $reencoded, [
            'ServerSideEncryption' => 'aws:kms',
        ]);

        return InterventionPhoto::create([
            'structure_id' => $intervention->structure_id,
            'intervention_id' => $intervention->id,
            'disk' => 's3',
            'path' => $path,
            'mime_type' => 'image/jpeg',
            'size_bytes' => mb_strlen($reencoded, '8bit'),
            // Sanitised filename — strip path-traversal characters and any
            // HTML-ish bytes. The original name is stored only for the UI;
            // file path is always the UUID above.
            'original_name' => $this->sanitizeFilename($file->getClientOriginalName()),
            'uploaded_by' => $uploader->id,
        ]);
    }

    public function deletePhoto(InterventionPhoto $photo): void
    {
        Storage::disk($photo->disk)->delete($photo->path);
        $photo->delete();
    }

    public function storeSignature(
        Intervention $intervention,
        string $base64Png,
        string $signerType,
        ?User $signer = null,
    ): InterventionSignature {
        // Strip data-URL prefix if the client sent one (data:image/png;base64,...).
        $base64 = preg_replace('#^data:image/\w+;base64,#', '', $base64Png) ?? $base64Png;

        $decoded = base64_decode($base64, strict: true);
        if ($decoded === false) {
            throw new HttpException(422, 'Signature invalide (base64 malformé).');
        }

        // Wave 1 / H8 — bound decoded size as a defense-in-depth check on
        // top of the request rule (signature.max:1500000 base64 chars).
        if (mb_strlen($decoded, '8bit') > self::SIGNATURE_MAX_DECODED_BYTES) {
            throw new HttpException(422, 'Signature trop volumineuse.');
        }

        $uuid = (string) Str::uuid();
        $path = "structures/{$intervention->structure_id}/interventions/{$intervention->id}/signatures/{$uuid}.png";

        Storage::disk('s3')->put($path, $decoded, [
            'ServerSideEncryption' => 'aws:kms',
        ]);

        return InterventionSignature::create([
            'structure_id' => $intervention->structure_id,
            'intervention_id' => $intervention->id,
            'disk' => 's3',
            'path' => $path,
            'signer_type' => $signerType,
            'signed_by' => $signer?->id,
            'signed_at' => now(),
        ]);
    }

    public function signedUrl(InterventionPhoto|InterventionSignature $media): string
    {
        return Storage::disk($media->disk)->temporaryUrl(
            $media->path,
            now()->addSeconds(self::SIGNED_URL_TTL),
        );
    }

    /**
     * Allowlist filename sanitisation. Strips everything but
     * `[A-Za-z0-9._-]`, collapses dots to a single separator, caps length.
     * Used only for the UI-visible original_name; the actual S3 path is
     * always a UUID so this is a display safeguard, not the primary
     * file-path defense.
     */
    private function sanitizeFilename(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?? '';
        $clean = preg_replace('/\.{2,}/', '.', $clean) ?? $clean;

        return mb_substr($clean, 0, 100) ?: 'photo';
    }
}
