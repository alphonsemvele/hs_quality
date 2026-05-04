<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * DocumentLibraryService — uploads procedure / fiche-pratique / formulaire
 * files to S3 SSE-KMS, manages monotonic versioning per title, enforces
 * the role-based ACL on download, and issues short-lived signed URLs.
 * Spec: PHASE2_PROGRESS.md M4.11.
 *
 * EXIF strip applies only to image MIMEs (the same Intervention-Image
 * pipeline used by InterventionMediaService); PDFs, .docx, .xlsx etc.
 * are stored as-is — re-encoding would corrupt them.
 *
 * Versioning is per (structure, title): re-uploading "Procédure soins"
 * creates v2 alongside v1, never overwrites. Older versions stay
 * available for audit; the latest version is whichever has the highest
 * `version` for that title.
 */
class DocumentLibraryService
{
    public const SIGNED_URL_TTL_SECONDS = 3600;

    public const MAX_BYTES = 25 * 1024 * 1024;

    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    private const ALLOWED_MIMES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * @param  array<int, string>|null  $rolesAcl
     */
    public function upload(
        Structure $structure,
        User $uploader,
        UploadedFile $file,
        string $title,
        ?string $description = null,
        ?array $rolesAcl = null,
    ): Document {
        $mime = $file->getMimeType();
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new HttpException(422, 'Type de fichier non autorisé.');
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw new HttpException(422, 'Le document ne doit pas dépasser 25 Mo.');
        }

        if (in_array($mime, self::IMAGE_MIMES, true)) {
            $bytes = (string) (new ImageManager(new GdDriver))
                ->decodePath($file->getRealPath())
                ->encode(new JpegEncoder(quality: 85));
            $storedMime = 'image/jpeg';
            $extension = 'jpg';
        } else {
            $bytes = file_get_contents($file->getRealPath());
            if ($bytes === false) {
                throw new HttpException(500, 'Lecture du fichier impossible.');
            }
            $storedMime = $mime;
            $extension = $file->getClientOriginalExtension() ?: 'bin';
        }

        $uuid = (string) Str::uuid();
        $path = "structures/{$structure->id}/documents/{$uuid}.{$extension}";

        Storage::disk('s3')->put($path, $bytes, [
            'ServerSideEncryption' => 'aws:kms',
        ]);

        return Document::create([
            'structure_id' => $structure->id,
            'title' => $title,
            'description' => $description,
            'disk' => 's3',
            'path' => $path,
            'mime_type' => $storedMime,
            'size_bytes' => mb_strlen($bytes, '8bit'),
            'version' => $this->nextVersionFor($structure, $title),
            'roles_acl' => $rolesAcl,
            'uploaded_by' => $uploader->id,
        ]);
    }

    /**
     * Issue a temporary signed URL after enforcing the role ACL.
     */
    public function downloadUrl(Document $document, User $user): string
    {
        if (! $document->isVisibleTo($user)) {
            throw new HttpException(403, 'Vous n’avez pas accès à ce document.');
        }

        return Storage::disk($document->disk)->temporaryUrl(
            $document->path,
            now()->addSeconds(self::SIGNED_URL_TTL_SECONDS),
        );
    }

    /**
     * Compute the next version number for (structure, title). Atomic
     * enough for this domain — concurrent uploads of the same title by
     * two coordinateurs at the same exact second is not a real risk;
     * a unique constraint on (structure_id, title, version) would be
     * the next step if it ever became one.
     */
    private function nextVersionFor(Structure $structure, string $title): int
    {
        $max = Document::query()
            ->where('structure_id', $structure->id)
            ->where('title', $title)
            ->max('version');

        return ($max ?? 0) + 1;
    }
}
