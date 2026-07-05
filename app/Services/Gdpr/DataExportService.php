<?php

declare(strict_types=1);

namespace App\Services\Gdpr;

use App\Enums\DataExportStatus;
use App\Models\DataExportRequest;
use App\Notifications\Gdpr\DataExportReadyNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

/**
 * GDPR data export (article 15 — right of access, article 20 — portability).
 *
 * Aggregates everything the platform stores about a single user into a
 * portable ZIP archive (JSON per category). Archives are uploaded to the
 * configured GDPR disk (typically S3 with SSE-KMS) and expire after 7
 * days so the link in the email becomes inert.
 *
 * The service is intentionally read-only on user data — no destructive
 * action happens here. See AccountDeletionService for the erasure path.
 */
class DataExportService
{
    private const DISK = 's3';

    private const RETENTION_DAYS = 7;

    public function process(DataExportRequest $request): void
    {
        $request->update([
            'status' => DataExportStatus::Processing,
        ]);

        try {
            $payload = $this->collectPayload($request->user);
            [$disk, $path, $size] = $this->writeArchive($request, $payload);

            $request->update([
                'status' => DataExportStatus::Ready,
                'archive_disk' => $disk,
                'archive_path' => $path,
                'archive_size_bytes' => $size,
                'processed_at' => now(),
                'expires_at' => now()->addDays(self::RETENTION_DAYS),
            ]);

            $request->user->notify(new DataExportReadyNotification($request));
        } catch (Throwable $e) {
            Log::error('GDPR export failed', [
                'request_id' => $request->id,
                'user_id' => $request->user_id,
                'error' => $e->getMessage(),
            ]);

            $request->update([
                'status' => DataExportStatus::Failed,
                'failure_reason' => substr($e->getMessage(), 0, 500),
                'processed_at' => now(),
            ]);
        }
    }

    /**
     * Build a signed, short-lived download URL for a ready archive. Falls
     * back to a plain URL when the disk doesn't support signing (local).
     */
    public function downloadUrl(DataExportRequest $request): ?string
    {
        if (! $request->status->isDownloadable() || $request->archive_path === null) {
            return null;
        }

        $disk = Storage::disk($request->archive_disk ?? self::DISK);

        try {
            return $disk->temporaryUrl($request->archive_path, now()->addMinutes(10));
        } catch (Throwable) {
            return $disk->url($request->archive_path);
        }
    }

    /**
     * @return array<string, mixed> categorised personal data for the user
     */
    private function collectPayload($user): array
    {
        $user->loadMissing(['structure']);

        return [
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'generated_by' => 'QualitéDomicile platform',
                'gdpr_articles' => ['Art. 15 — right of access', 'Art. 20 — portability'],
                'retention_days' => self::RETENTION_DAYS,
                'structure' => [
                    'id' => $user->structure?->id,
                    'name' => $user->structure?->name,
                ],
            ],
            'profile' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'employee_number' => $user->employee_number,
                'type' => $user->type instanceof \BackedEnum ? $user->type->value : (string) $user->type,
                'specialty' => $user->specialty,
                'hired_at' => $user->hired_at?->toDateString(),
                'status' => $user->status,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'two_factor_confirmed_at' => $user->two_factor_confirmed_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'notifications' => $user->notifications()
                ->orderByDesc('created_at')
                ->limit(500)
                ->get(['id', 'type', 'data', 'read_at', 'created_at'])
                ->map(fn ($n) => [
                    'id' => $n->id,
                    'type' => $n->type,
                    'data' => $n->data,
                    'read_at' => $n->read_at?->toIso8601String(),
                    'received_at' => $n->created_at?->toIso8601String(),
                ])
                ->all(),
            'api_tokens' => $user->tokens()
                ->get(['id', 'name', 'last_used_at', 'created_at'])
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'last_used_at' => $t->last_used_at?->toIso8601String(),
                    'created_at' => $t->created_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0:string,1:string,2:int} disk, path, size
     */
    private function writeArchive(DataExportRequest $request, array $payload): array
    {
        $tmpDir = sys_get_temp_dir();
        $tmpFile = $tmpDir.DIRECTORY_SEPARATOR.'gdpr-export-'.$request->id.'.zip';

        $zip = new ZipArchive;
        if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to open ZIP archive for writing.');
        }

        $zip->addFromString('README.txt', $this->readmeContents($request));

        foreach ($payload as $category => $data) {
            $zip->addFromString(
                sprintf('%s.json', $category),
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );
        }

        $zip->close();

        $contents = file_get_contents($tmpFile);
        $size = strlen($contents);
        $path = sprintf('gdpr-exports/%s/%s.zip', $request->structure_id, $request->id);

        Storage::disk(self::DISK)->put($path, $contents, ['visibility' => 'private']);

        @unlink($tmpFile);

        return [self::DISK, $path, $size];
    }

    private function readmeContents(DataExportRequest $request): string
    {
        return <<<TXT
            QualitéDomicile — Export de vos données personnelles
            ----------------------------------------------------

            Identifiant de la demande : {$request->id}
            Généré le : {$request->updated_at?->toIso8601String()}
            Conservation de l'archive : 7 jours

            Cette archive contient les données personnelles vous concernant que la
            plateforme QualitéDomicile détient à la date de génération, conformément
            aux articles 15 et 20 du RGPD.

            Contenu :
              - profile.json        : vos informations de compte
              - notifications.json  : les notifications reçues sur la plateforme
              - api_tokens.json     : la liste de vos jetons d'accès API

            Les données de santé des bénéficiaires que vous accompagnez ne sont
            PAS incluses : elles sont la propriété de la structure cliente, qui
            est responsable de leur traitement (article 28 RGPD).

            Pour toute question : dpo@hsquality.fr
            TXT;
    }
}
