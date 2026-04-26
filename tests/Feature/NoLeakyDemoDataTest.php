<?php

declare(strict_types=1);

/**
 * Wave 1 / C1 — guard against reintroducing named-individual demo data
 * into controllers, services, seeders, or React pages.
 *
 * Background: a previous iteration of the dashboard, four "stub" controllers,
 * and several React pages embedded a hardcoded list of plausible French names
 * (Sophie Ateba, Bruno Ngono, Marie Essomba, etc.) — including one with a
 * QVCT mental-health distress flag. That data rendered to every authenticated
 * user across every tenant, which is an RGPD breach.
 *
 * If this test fails, you have re-introduced one of those names. Either:
 *   1. Use a faker-driven dataset (Beneficiary::factory(), User::factory()).
 *   2. Render an empty state.
 *   3. Render a Phase-2 "coming soon" placeholder (see dashboard/coming-soon).
 */

use Symfony\Component\Finder\Finder;

it('does not embed real-looking named individuals in controllers/pages/seeders', function (): void {
    $forbidden = [
        // Names that previously appeared in hardcoded sample data.
        'Sophie Ateba',
        'Bruno Ngono',
        'Marie Essomba',
        'Pascaline Eko',
        'Jean Koffi',
        'Amina Fofana',
        'Clément Touré',
        'Paul Biya Jr',
        'Fatima Ndiaye',
        'Pierre Mbarga',
    ];

    $finder = Finder::create()
        ->in([
            base_path('app'),
            base_path('resources/js'),
            base_path('database/seeders'),
        ])
        ->files()
        ->name(['*.php', '*.tsx', '*.ts'])
        // Allow this test file to mention the names in its forbidden list.
        ->notName('NoLeakyDemoDataTest.php');

    $offenders = [];

    foreach ($finder as $file) {
        $contents = $file->getContents();
        foreach ($forbidden as $name) {
            if (str_contains($contents, $name)) {
                $offenders[] = $file->getRelativePathname().' contains "'.$name.'"';
            }
        }
    }

    expect($offenders)->toBe([], "Hardcoded named individuals found:\n".implode("\n", $offenders));
});
