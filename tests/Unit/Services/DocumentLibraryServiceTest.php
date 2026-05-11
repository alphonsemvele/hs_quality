<?php

declare(strict_types=1);

use App\Models\Document;
use App\Services\DocumentLibraryService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\FileFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Storage::fake('s3');

    $this->service = app(DocumentLibraryService::class);
    $this->uploader = actingAsRole('coordinateur');
    $this->structure = $this->uploader->structure;
});

// ── upload — validation ──────────────────────────────────────────────────

it('rejects an unsupported MIME type', function (): void {
    $file = UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload');

    expect(fn () => $this->service->upload(
        $this->structure, $this->uploader, $file, 'Procédure'
    ))->toThrow(HttpException::class, 'Type de fichier non autorisé.');
});

it('rejects an oversized file', function (): void {
    // 26 MB > 25 MB limit
    $file = UploadedFile::fake()->create('big.pdf', 26 * 1024, 'application/pdf');

    expect(fn () => $this->service->upload(
        $this->structure, $this->uploader, $file, 'Gros doc'
    ))->toThrow(HttpException::class);
});

// ── upload — happy paths ─────────────────────────────────────────────────

it('uploads a PDF as-is (no re-encoding)', function (): void {
    $file = UploadedFile::fake()->create('proc.pdf', 50, 'application/pdf');

    $doc = $this->service->upload(
        $this->structure, $this->uploader, $file, 'Procédure soins',
    );

    expect($doc)->toBeInstanceOf(Document::class)
        ->and($doc->mime_type)->toBe('application/pdf')
        ->and($doc->path)->toEndWith('.pdf')
        ->and($doc->version)->toBe(1)
        ->and($doc->structure_id)->toBe($this->structure->id)
        ->and($doc->uploaded_by)->toBe($this->uploader->id);

    expect(Storage::disk('s3')->exists($doc->path))->toBeTrue();
});

it('re-encodes uploaded images to JPEG (EXIF strip)', function (): void {
    $file = (new FileFactory)->image('photo.png', 200, 200);

    $doc = $this->service->upload(
        $this->structure, $this->uploader, $file, 'Photo procédure',
    );

    expect($doc->mime_type)->toBe('image/jpeg')
        ->and($doc->path)->toEndWith('.jpg');

    $stored = Storage::disk('s3')->get($doc->path);
    expect(substr($stored, 0, 3))->toBe("\xFF\xD8\xFF"); // JPEG magic
})->skip(
    fn () => ! extension_loaded('gd'),
    'php-gd required for Intervention Image re-encode.',
);

// ── versioning ───────────────────────────────────────────────────────────

it('increments version on re-upload of the same title', function (): void {
    $f1 = UploadedFile::fake()->create('v1.pdf', 50, 'application/pdf');
    $f2 = UploadedFile::fake()->create('v2.pdf', 50, 'application/pdf');
    $f3 = UploadedFile::fake()->create('v3.pdf', 50, 'application/pdf');

    $v1 = $this->service->upload($this->structure, $this->uploader, $f1, 'Fiche pratique chute');
    $v2 = $this->service->upload($this->structure, $this->uploader, $f2, 'Fiche pratique chute');
    $v3 = $this->service->upload($this->structure, $this->uploader, $f3, 'Fiche pratique chute');

    expect($v1->version)->toBe(1)
        ->and($v2->version)->toBe(2)
        ->and($v3->version)->toBe(3);
});

it('versions are independent across titles', function (): void {
    $a = UploadedFile::fake()->create('a.pdf', 50, 'application/pdf');
    $b = UploadedFile::fake()->create('b.pdf', 50, 'application/pdf');

    $aDoc = $this->service->upload($this->structure, $this->uploader, $a, 'Procédure A');
    $bDoc = $this->service->upload($this->structure, $this->uploader, $b, 'Procédure B');

    expect($aDoc->version)->toBe(1)->and($bDoc->version)->toBe(1);
});

// ── ACL gate on download ────────────────────────────────────────────────

it('issues a signed URL when the user is in the ACL', function (): void {
    $intervenant = actingAsRole('intervenant', $this->structure);

    $doc = Document::factory()
        ->forStructure($this->structure)
        ->visibleToRoles(['intervenant'])
        ->create(['uploaded_by' => $this->uploader->id]);

    $url = $this->service->downloadUrl($doc, $intervenant);

    expect($url)->toBeString()->and($url)->toContain($doc->path);
});

it('issues a signed URL for a structure-wide doc (empty ACL)', function (): void {
    $intervenant = actingAsRole('intervenant', $this->structure);

    $doc = Document::factory()
        ->forStructure($this->structure)
        ->create(['uploaded_by' => $this->uploader->id, 'roles_acl' => null]);

    $url = $this->service->downloadUrl($doc, $intervenant);

    expect($url)->toBeString();
});

it('rejects download when the user role is not in the ACL', function (): void {
    $intervenant = actingAsRole('intervenant', $this->structure);

    $doc = Document::factory()
        ->forStructure($this->structure)
        ->visibleToRoles(['dirigeant', 'referent_qualite'])
        ->create(['uploaded_by' => $this->uploader->id]);

    expect(fn () => $this->service->downloadUrl($doc, $intervenant))
        ->toThrow(HttpException::class);
});
