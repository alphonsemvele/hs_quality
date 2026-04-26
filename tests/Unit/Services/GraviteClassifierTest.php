<?php

use App\Enums\CategorieIncident;
use App\Enums\GraviteIncident;
use App\Services\GraviteClassifier;

// Death always → critique regardless of category
it('classifies as critique when death occurred', function (CategorieIncident $cat) {
    $result = GraviteClassifier::classify($cat, avecDeces: true);
    expect($result)->toBe(GraviteIncident::Critique);
})->with(CategorieIncident::cases());

// Inherently critical categories
it('classifies maltraitance_suspecte as critique', function () {
    expect(GraviteClassifier::classify(CategorieIncident::MaltraitanceSuspecte))
        ->toBe(GraviteIncident::Critique);
});

it('classifies situation_danger as critique', function () {
    expect(GraviteClassifier::classify(CategorieIncident::SituationDanger))
        ->toBe(GraviteIncident::Critique);
});

// Hospitalisation escalates to grave
it('classifies as grave when hospitalisation occurred', function () {
    expect(GraviteClassifier::classify(CategorieIncident::Chute, avecHospitalisation: true))
        ->toBe(GraviteIncident::Grave);
});

// Medication error → grave even without hospitalisation
it('classifies erreur_medicamenteuse as grave', function () {
    expect(GraviteClassifier::classify(CategorieIncident::ErreurMedicamenteuse))
        ->toBe(GraviteIncident::Grave);
});

// Physical injury → significatif
it('classifies as significatif when physical injury present', function () {
    expect(GraviteClassifier::classify(CategorieIncident::Chute, avecBlessurePhysique: true))
        ->toBe(GraviteIncident::Significatif);
});

// Aggression without injury or hospitalisation → significatif
it('classifies agression as significatif', function () {
    expect(GraviteClassifier::classify(CategorieIncident::Agression))
        ->toBe(GraviteIncident::Significatif);
});

// Default → mineur
it('classifies chute without consequences as mineur', function () {
    expect(GraviteClassifier::classify(CategorieIncident::Chute))
        ->toBe(GraviteIncident::Mineur);
});

it('classifies autre without consequences as mineur', function () {
    expect(GraviteClassifier::classify(CategorieIncident::Autre))
        ->toBe(GraviteIncident::Mineur);
});

// Death overrides everything — even medication error
it('death overrides erreur_medicamenteuse to critique', function () {
    expect(GraviteClassifier::classify(
        CategorieIncident::ErreurMedicamenteuse,
        avecDeces: true,
        avecHospitalisation: true,
    ))->toBe(GraviteIncident::Critique);
});
