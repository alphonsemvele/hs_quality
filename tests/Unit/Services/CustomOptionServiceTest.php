<?php

declare(strict_types=1);

use App\Models\CustomOption;
use App\Models\Structure;
use App\Services\CustomOptionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
    $this->service = app(CustomOptionService::class);
});

it('merges enum options with active custom options, enum first', function (): void {
    CustomOption::factory()->create([
        'structure_id' => $this->structure->id,
        'field_key' => 'gir',
        'value' => 'non_evalue',
        'label' => 'Non évalué',
        'sort_order' => 0,
        'is_active' => true,
    ]);

    $enum = [
        ['value' => '1', 'label' => 'GIR 1'],
        ['value' => '6', 'label' => 'GIR 6'],
    ];

    $result = $this->service->mergeWithEnum($enum, 'gir');

    expect($result)->toHaveCount(3);
    expect($result[0]['value'])->toBe('1');
    expect($result[1]['value'])->toBe('6');
    expect($result[2]['value'])->toBe('non_evalue');
});

it('excludes inactive custom options', function (): void {
    CustomOption::factory()->create([
        'structure_id' => $this->structure->id,
        'field_key' => 'gir',
        'value' => 'inactive_level',
        'label' => 'Inactif',
        'is_active' => false,
    ]);

    $result = $this->service->mergeWithEnum([], 'gir');

    expect($result)->toBeEmpty();
});

it('allowedValues returns enum values plus custom option values', function (): void {
    CustomOption::factory()->create([
        'structure_id' => $this->structure->id,
        'field_key' => 'gir',
        'value' => 'non_evalue',
        'label' => 'Non évalué',
        'is_active' => true,
    ]);

    $allowed = $this->service->allowedValues('gir', ['1', '2', '3', '4', '5', '6']);

    expect($allowed)->toContain('1', '6', 'non_evalue');
    expect($allowed)->toHaveCount(7);
});
