<?php

declare(strict_types=1);

use App\Enums\CustomOptionField;
use App\Models\CustomOption;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

// ── Index ──────────────────────────────────────────────────────────────────────

it('returns custom options for a valid field key', function (): void {
    $dirigeant = actingAsRole('dirigeant');

    CustomOption::factory()->forField(CustomOptionField::Gir)->create([
        'structure_id' => $dirigeant->structure_id,
        'value' => 'non_evalue',
        'label' => 'Non évalué',
    ]);

    $response = $this->getJson('/settings/options/gir');

    $response->assertOk();
    $response->assertJsonFragment(['value' => 'non_evalue', 'label' => 'Non évalué']);
});

it('returns 422 for a field key not in the whitelist', function (): void {
    actingAsRole('dirigeant');

    $this->getJson('/settings/options/gravite_incident')->assertStatus(422);
});

// ── Store ──────────────────────────────────────────────────────────────────────

it('allows dirigeant to create a custom option', function (): void {
    actingAsRole('dirigeant');

    $response = $this->postJson('/settings/options', [
        'field_key' => 'gir',
        'value' => 'non_evalue',
        'label' => 'Non évalué',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('custom_options', ['value' => 'non_evalue', 'label' => 'Non évalué']);
});

it('allows referent_qualite to create a custom option', function (): void {
    actingAsRole('referent_qualite');

    $response = $this->postJson('/settings/options', [
        'field_key' => 'categorie_incident',
        'value' => 'chute_voie_publique',
        'label' => 'Chute (voie publique)',
    ]);

    $response->assertCreated();
});

it('blocks intervenant from creating a custom option', function (): void {
    actingAsRole('intervenant');

    $this->postJson('/settings/options', [
        'field_key' => 'gir',
        'value' => 'test',
        'label' => 'Test',
    ])->assertForbidden();
});

it('blocks coordinateur from creating a custom option', function (): void {
    actingAsRole('coordinateur');

    $this->postJson('/settings/options', [
        'field_key' => 'gir',
        'value' => 'test',
        'label' => 'Test',
    ])->assertForbidden();
});

it('rejects a field_key not in the whitelist', function (): void {
    actingAsRole('dirigeant');

    $this->postJson('/settings/options', [
        'field_key' => 'gravite_incident',
        'value' => 'custom',
        'label' => 'Custom',
    ])->assertUnprocessable();
});

it('rejects a duplicate value for the same field and structure', function (): void {
    $dirigeant = actingAsRole('dirigeant');

    CustomOption::factory()->forField(CustomOptionField::Gir)->create([
        'structure_id' => $dirigeant->structure_id,
        'value' => 'non_evalue',
        'label' => 'Non évalué',
    ]);

    $this->postJson('/settings/options', [
        'field_key' => 'gir',
        'value' => 'non_evalue',
        'label' => 'Duplicate',
    ])->assertUnprocessable();
});

it('accepts the same value for a different structure', function (): void {
    actingAsRole('dirigeant');

    $this->postJson('/settings/options', [
        'field_key' => 'gir',
        'value' => 'non_evalue',
        'label' => 'Non évalué',
    ])->assertCreated();

    // Second structure with same value — must pass
    actingAsRole('dirigeant');

    $this->postJson('/settings/options', [
        'field_key' => 'gir',
        'value' => 'non_evalue',
        'label' => 'Non évalué',
    ])->assertCreated();
});

it('rejects a value containing invalid characters', function (): void {
    actingAsRole('dirigeant');

    $this->postJson('/settings/options', [
        'field_key' => 'gir',
        'value' => 'Valeur Invalide!',
        'label' => 'Test',
    ])->assertUnprocessable();
});

// ── Update ─────────────────────────────────────────────────────────────────────

it('allows dirigeant to update label and sort_order', function (): void {
    $dirigeant = actingAsRole('dirigeant');

    $option = CustomOption::factory()->forField(CustomOptionField::Gir)->create([
        'structure_id' => $dirigeant->structure_id,
    ]);

    $this->putJson("/settings/options/{$option->id}", [
        'label' => 'Nouveau libellé',
        'sort_order' => 5,
    ])->assertOk()->assertJsonFragment(['label' => 'Nouveau libellé', 'sort_order' => 5]);
});

it('cannot change the value on update', function (): void {
    $dirigeant = actingAsRole('dirigeant');

    $option = CustomOption::factory()->forField(CustomOptionField::Gir)->create([
        'structure_id' => $dirigeant->structure_id,
        'value' => 'original',
    ]);

    $this->putJson("/settings/options/{$option->id}", ['label' => 'New']);

    // value must remain unchanged
    expect($option->fresh()->value)->toBe('original');
});

// ── Destroy ────────────────────────────────────────────────────────────────────

it('soft-deletes an option and it no longer appears in index', function (): void {
    $dirigeant = actingAsRole('dirigeant');

    $option = CustomOption::factory()->forField(CustomOptionField::Gir)->create([
        'structure_id' => $dirigeant->structure_id,
        'value' => 'to_delete',
        'label' => 'To delete',
    ]);

    $this->deleteJson("/settings/options/{$option->id}")->assertNoContent();

    $this->assertSoftDeleted('custom_options', ['id' => $option->id]);

    $this->getJson('/settings/options/gir')
        ->assertJsonMissing(['value' => 'to_delete']);
});

// ── Cross-tenant leak test ─────────────────────────────────────────────────────

it('does not leak custom options across structures', function (): void {
    $structureA = Structure::factory()->create(['code' => 'OPT-A']);
    $structureB = Structure::factory()->create(['code' => 'OPT-B']);

    CustomOption::factory()->forField(CustomOptionField::Gir)->create([
        'structure_id' => $structureA->id,
        'value' => 'secret_level',
        'label' => 'Niveau secret de A',
    ]);

    // Act as structure B
    actingAsRole('dirigeant', $structureB);

    $response = $this->getJson('/settings/options/gir');
    $response->assertOk();
    $response->assertJsonMissing(['value' => 'secret_level']);

    // Also assert at DB level
    $visibleIds = CustomOption::query()->forField('gir')->pluck('id');
    expect($visibleIds)->not->toContain(
        CustomOption::withoutGlobalScopes()->where('value', 'secret_level')->first()?->id
    );
});
