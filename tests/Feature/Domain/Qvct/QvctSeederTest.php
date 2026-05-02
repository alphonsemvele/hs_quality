<?php

declare(strict_types=1);

use App\Models\QvctQuestionnaire;
use App\Models\Structure;
use Database\Seeders\QvctSeeder;

it('seeds a default questionnaire per structure', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    (new QvctSeeder)->run();

    expect(QvctQuestionnaire::withoutGlobalScopes()->where('structure_id', $a->id)->where('title', QvctSeeder::DEFAULT_TITLE)->count())->toBe(1);
    expect(QvctQuestionnaire::withoutGlobalScopes()->where('structure_id', $b->id)->where('title', QvctSeeder::DEFAULT_TITLE)->count())->toBe(1);
});

it('is idempotent — running twice does not duplicate questionnaires', function (): void {
    Structure::factory()->create();

    (new QvctSeeder)->run();
    (new QvctSeeder)->run();

    expect(QvctQuestionnaire::withoutGlobalScopes()->where('title', QvctSeeder::DEFAULT_TITLE)->count())->toBe(1);
});

it('default questionnaire carries the four CDC categories', function (): void {
    Structure::factory()->create();
    (new QvctSeeder)->run();

    $q = QvctQuestionnaire::withoutGlobalScopes()->where('title', QvctSeeder::DEFAULT_TITLE)->first();
    expect($q)->not->toBeNull();
    $categories = collect($q->questions)->pluck('category')->all();

    expect($categories)->toContain(
        'baisse_morale',
        'surcharge',
        'conflit_relationnel',
        'isolement_professionnel',
    );
});
