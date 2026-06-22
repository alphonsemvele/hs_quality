<?php

declare(strict_types=1);

use App\Enums\AuditGridSource;
use App\Services\AuditGridLibrary;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('renders the grid show page grouped by axe with Niveau and Sources props', function (): void {
    $rq = actingAsRole('referent_qualite');
    $grid = app(AuditGridLibrary::class)->provisionForStructure($rq->structure, AuditGridSource::Has);

    $response = $this->get("/audits/grids/{$grid->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/audits/grids/show')
        ->has('grid', fn ($g) => $g
            ->where('items_count', 75)
            ->where('imperatif_count', fn ($v) => $v >= 15)
            ->etc()
        )
        ->has('axes', 10)
        ->has('items', 75, fn ($i) => $i
            ->has('id')
            ->has('axis_id')
            ->has('title')
            ->has('level')
            ->has('sources')
            ->has('scale')
            ->has('max_points')
            ->has('position')
            ->etc()
        )
    );
});
