<?php

use App\Models\Beneficiary;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;

/**
 * HTTP tests for the contacts surface — show + update. Verifies that the
 * page exposes ONLY non-medical fields and that the update endpoint
 * refuses to touch encrypted health-data columns.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('renders the contacts page without leaking medical fields', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()
        ->forStructure($user->structure)
        ->withMedicalNotes('confidential')
        ->create([
            'phone' => '0612345678',
            'email' => 'famille@exemple.fr',
            'primary_doctor' => 'Dr Test',
            'emergency_contact_name' => 'Marie Doe',
        ]);

    $this->get("/beneficiaries/{$beneficiary->id}/contacts")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/beneficiaries/contacts')
            ->where('beneficiary.phone', '0612345678')
            ->where('beneficiary.primary_doctor', 'Dr Test')
            ->where('beneficiary.emergency_contact_name', 'Marie Doe')
            ->missing('beneficiary.medical_notes')
            ->missing('beneficiary.allergies')
            ->missing('beneficiary.medical_history')
            ->missing('beneficiary.current_treatments')
        );
});

it('updates non-medical contact fields', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()
        ->forStructure($user->structure)
        ->create(['phone' => null, 'primary_doctor' => null]);

    $this->put("/beneficiaries/{$beneficiary->id}/contacts", [
        'phone' => '0698765432',
        'email' => 'new@exemple.fr',
        'primary_doctor' => 'Dr New',
        'emergency_contact_name' => 'Jean Dupont',
        'emergency_contact_phone' => '0611111111',
        'emergency_contact_relationship' => 'Fils',
    ])->assertRedirect("/beneficiaries/{$beneficiary->id}/contacts");

    $beneficiary->refresh();
    expect($beneficiary->phone)->toBe('0698765432');
    expect($beneficiary->email)->toBe('new@exemple.fr');
    expect($beneficiary->primary_doctor)->toBe('Dr New');
    expect($beneficiary->emergency_contact_name)->toBe('Jean Dupont');
    expect($beneficiary->emergency_contact_relationship)->toBe('Fils');
});

it('refuses to update encrypted medical fields through the contacts endpoint', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()
        ->forStructure($user->structure)
        ->withMedicalNotes('original allergy')
        ->create();

    $this->put("/beneficiaries/{$beneficiary->id}/contacts", [
        'phone' => '0699999999',
        // Attempt to slip medical fields through.
        'allergies' => 'tampered',
        'medical_notes' => 'tampered',
        'medical_history' => 'tampered',
        'current_treatments' => 'tampered',
    ])->assertRedirect();

    $beneficiary->refresh();
    expect($beneficiary->phone)->toBe('0699999999');
    expect($beneficiary->allergies)->not->toBe('tampered');
    expect($beneficiary->medical_notes)->not->toBe('tampered');
});

it('returns 404 on the contacts page of another tenants beneficiary', function () {
    actingAsRole('coordinateur');
    $structureB = Structure::factory()->create();
    $beneficiaryB = Beneficiary::factory()->forStructure($structureB)->create();

    $this->get("/beneficiaries/{$beneficiaryB->id}/contacts")
        ->assertNotFound();
});

it('forbids unauthenticated access to the contacts page', function () {
    $beneficiary = Beneficiary::factory()
        ->forStructure(Structure::factory()->create())
        ->create();

    $this->get("/beneficiaries/{$beneficiary->id}/contacts")
        ->assertRedirect('/login');
});
