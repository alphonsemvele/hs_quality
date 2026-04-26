<?php

use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Models\InterventionPhoto;
use App\Models\InterventionSignature;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/** Return an UploadedFile backed by the real 1×1 PNG fixture. */
function fakePng(string $name = 'photo.jpg'): UploadedFile
{
    $fixture = __DIR__.'/fixtures/1x1.png';

    return new UploadedFile($fixture, $name, 'image/jpeg', null, true);
}

beforeEach(function () {
    Storage::fake('s3');
    $this->seed(RoleSeeder::class);
});

// ── PHOTOS ─────────────────────────────────────────────────────────────────────

it('coordinator can upload a jpeg photo', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->inProgress()
        ->create();

    $response = $this->post("/interventions/{$intervention->id}/photos", ['photo' => fakePng()]);

    $response->assertRedirect();
    expect(InterventionPhoto::count())->toBe(1);

    $photo = InterventionPhoto::first();
    Storage::disk('s3')->assertExists($photo->path);
    expect($photo->structure_id)->toBe($coord->structure_id)
        ->and($photo->intervention_id)->toBe($intervention->id)
        ->and($photo->uploaded_by)->toBe($coord->id);
})->skip(
    fn () => ! extension_loaded('gd'),
    'php-gd required for InterventionMediaService re-encoding (Wave 1 / H8).',
);

it('rejects a photo exceeding 5 MB', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->inProgress()
        ->create();

    $oversize = UploadedFile::fake()->createWithContent(
        'big.jpg',
        str_repeat('x', 6 * 1024 * 1024),
    );

    $response = $this->post("/interventions/{$intervention->id}/photos", ['photo' => $oversize]);

    $response->assertSessionHasErrors('photo');
    expect(InterventionPhoto::count())->toBe(0);
});

it('rejects a non-image file type', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->inProgress()
        ->create();

    $file = new UploadedFile(
        __DIR__.'/fixtures/1x1.png',
        'script.php',
        'text/x-php',
        null,
        true,
    );

    $response = $this->post("/interventions/{$intervention->id}/photos", ['photo' => $file]);

    $response->assertSessionHasErrors('photo');
    expect(InterventionPhoto::count())->toBe(0);
});

it('returns 404 when uploading a photo to a foreign intervention', function () {
    actingAsRole('coordinateur');
    $other = Structure::factory()->create();
    $otherBeneficiary = Beneficiary::factory()->forStructure($other)->create();
    $otherIntervenant = User::factory()->create(['structure_id' => $other->id, 'type' => 'intervenant']);
    $foreignIntervention = Intervention::factory()
        ->forBeneficiary($otherBeneficiary)
        ->forIntervenant($otherIntervenant)
        ->inProgress()
        ->create();

    $response = $this->post("/interventions/{$foreignIntervention->id}/photos", ['photo' => fakePng()]);

    $response->assertNotFound();
    expect(InterventionPhoto::count())->toBe(0);
});

it('coordinator can delete a photo', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->inProgress()
        ->create();

    $this->post("/interventions/{$intervention->id}/photos", ['photo' => fakePng()]);

    $photo = InterventionPhoto::first();
    Storage::disk('s3')->assertExists($photo->path);

    $this->delete("/interventions/{$intervention->id}/photos/{$photo->id}")->assertRedirect();

    expect(InterventionPhoto::count())->toBe(0);
    Storage::disk('s3')->assertMissing($photo->path);
})->skip(
    fn () => ! extension_loaded('gd'),
    'php-gd required for InterventionMediaService re-encoding (Wave 1 / H8).',
);

// ── SIGNATURES ─────────────────────────────────────────────────────────────────

it('coordinator can store a beneficiary signature', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->inProgress()
        ->create();

    $pngBase64 = base64_encode(file_get_contents(__DIR__.'/fixtures/1x1.png'));

    $response = $this->post("/interventions/{$intervention->id}/signature", [
        'signature' => $pngBase64,
        'signer_type' => 'beneficiary',
    ]);

    $response->assertRedirect();
    expect(InterventionSignature::count())->toBe(1);

    $sig = InterventionSignature::first();
    Storage::disk('s3')->assertExists($sig->path);
    expect($sig->signer_type)->toBe('beneficiary')
        ->and($sig->structure_id)->toBe($coord->structure_id);
});

it('rejects an invalid signer_type', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->inProgress()
        ->create();

    $response = $this->post("/interventions/{$intervention->id}/signature", [
        'signature' => base64_encode('fake-data'),
        'signer_type' => 'unknown_type',
    ]);

    $response->assertSessionHasErrors('signer_type');
    expect(InterventionSignature::count())->toBe(0);
});
