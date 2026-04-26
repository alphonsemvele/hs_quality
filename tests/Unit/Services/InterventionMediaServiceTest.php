<?php

declare(strict_types=1);

use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Services\InterventionMediaService;
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
});

function makeIntervention(): Intervention
{
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();

    return Intervention::factory()
        ->forStructure($coord->structure)
        ->state(['beneficiary_id' => $beneficiary->id, 'intervenant_id' => $coord->id])
        ->create();
}

it('rejects a non-image MIME type', function (): void {
    $intervention = makeIntervention();
    $file = UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload');

    expect(fn () => app(InterventionMediaService::class)->storePhoto(
        $intervention, $file, $intervention->intervenant,
    ))->toThrow(HttpException::class, 'Type de fichier non autorisé.');
});

it('re-encodes the uploaded photo to JPEG (strips EXIF / payloads)', function (): void {
    $intervention = makeIntervention();

    $file = (new FileFactory)->image('original.png', 200, 200);

    $photo = app(InterventionMediaService::class)
        ->storePhoto($intervention, $file, $intervention->intervenant);

    expect($photo->mime_type)->toBe('image/jpeg');
    expect($photo->path)->toEndWith('.jpg');
    expect(Storage::disk('s3')->exists($photo->path))->toBeTrue();

    $stored = Storage::disk('s3')->get($photo->path);
    expect(substr($stored, 0, 3))->toBe("\xFF\xD8\xFF"); // JPEG magic bytes
})->skip(
    fn () => ! extension_loaded('gd'),
    'php-gd extension required (production uses Intervention Image with GD or Imagick).',
);

it('sanitises the original filename (strips path-traversal + scripts)', function (): void {
    $intervention = makeIntervention();
    $file = (new FileFactory)->image('<script>alert(1)</script>../../../evil.png', 50, 50);

    $photo = app(InterventionMediaService::class)
        ->storePhoto($intervention, $file, $intervention->intervenant);

    expect($photo->original_name)->not->toContain('<');
    expect($photo->original_name)->not->toContain('>');
    expect($photo->original_name)->not->toContain('/');
    expect($photo->original_name)->not->toContain('..');
})->skip(
    fn () => ! extension_loaded('gd'),
    'php-gd extension required.',
);

it('sanitiseFilename helper strips dangerous characters', function (): void {
    $service = app(InterventionMediaService::class);
    $method = (new ReflectionClass($service))->getMethod('sanitizeFilename');
    $method->setAccessible(true);

    foreach (['<', '>', '/', '..', "\0"] as $forbidden) {
        expect($method->invoke($service, "evil{$forbidden}name.png"))
            ->not->toContain($forbidden);
    }

    expect($method->invoke($service, ''))->toBe('photo');
    expect($method->invoke($service, str_repeat('a', 200)))->toHaveLength(100);
});

it('rejects a signature that decodes larger than 200 KB', function (): void {
    $intervention = makeIntervention();

    // 250 KB binary payload, base64-encoded.
    $oversize = base64_encode(random_bytes(250 * 1024));

    expect(fn () => app(InterventionMediaService::class)->storeSignature(
        $intervention, $oversize, 'beneficiary',
    ))->toThrow(HttpException::class, 'Signature trop volumineuse.');
});

it('strips a data-URL prefix from the signature payload', function (): void {
    $intervention = makeIntervention();
    $payload = 'data:image/png;base64,'.base64_encode("\x89PNG\r\n\x1a\n".str_repeat("\x00", 50));

    $sig = app(InterventionMediaService::class)
        ->storeSignature($intervention, $payload, 'beneficiary');

    expect(Storage::disk('s3')->exists($sig->path))->toBeTrue();
    $stored = Storage::disk('s3')->get($sig->path);
    // Should not contain the data: prefix any more.
    expect($stored)->not->toContain('data:image/png;base64,');
    // Should start with the PNG magic bytes.
    expect(substr($stored, 0, 4))->toBe("\x89PNG");
});
