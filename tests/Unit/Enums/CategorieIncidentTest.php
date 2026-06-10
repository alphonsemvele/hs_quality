<?php

declare(strict_types=1);

use App\Enums\CategorieIncident;

it('exposes a French label for every category', function (): void {
    foreach (CategorieIncident::cases() as $categorie) {
        expect($categorie->label())->toBeString()->not->toBe('');
    }
});

it('never returns the raw snake_case enum value as the label', function (CategorieIncident $categorie): void {
    // Regression guard: the indicators chart / incident list once showed raw
    // values like "erreur_medicamenteuse" instead of human-readable French.
    expect($categorie->label())->not->toContain('_');
})->with(CategorieIncident::cases());

it('maps the reported raw values to readable French', function (): void {
    expect(CategorieIncident::ErreurMedicamenteuse->label())->toBe('Erreur médicamenteuse')
        ->and(CategorieIncident::SituationDanger->label())->toBe('Situation de danger');
});
