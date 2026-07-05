<?php

declare(strict_types=1);

/**
 * Smoke tests — every public marketing/legal page must return 200 with an
 * Inertia component name matching the route. Catches the silent class of
 * regressions where a route is renamed but the route map / footer link
 * isn't updated.
 *
 * Public surface only — authenticated dashboard pages have their own
 * domain-level Feature tests.
 */
it('renders every public marketing page', function (string $path, string $component): void {
    $response = $this->get($path);

    $response->assertSuccessful();
    expect($response->viewData('page')['component'])->toBe($component);
})->with([
    'home' => ['/', 'index'],
    'features' => ['/fonctionnalites', 'marketing/features'],
    'compliance' => ['/conformite', 'marketing/compliance'],
    'tarifs' => ['/tarifs', 'marketing/tarifs'],
    'clients' => ['/clients', 'marketing/clients'],
    'changelog' => ['/changelog', 'marketing/changelog'],
    'contact' => ['/contact', 'marketing/contact'],
    'legal' => ['/mentions-legales', 'marketing/legal-mentions'],
    'privacy' => ['/confidentialite', 'marketing/privacy'],
    'cgu' => ['/cgu', 'marketing/cgu'],
    'accessibility' => ['/accessibilite', 'marketing/accessibility'],
    'cookies' => ['/cookies', 'marketing/cookies'],
    'registry' => ['/registre-traitements', 'marketing/registry'],
    'signup' => ['/inscription', 'marketing/signup'],
]);

it('renders the signup confirmation page even without flash data', function (): void {
    $response = $this->get('/inscription/confirmation');

    $response->assertSuccessful();
    expect($response->viewData('page')['component'])->toBe('marketing/signup-confirmation');
    expect($response->viewData('page')['props']['email'])->toBeNull();
});

it('exposes the signup email via flash when redirected from store', function (): void {
    $response = $this->withSession(['signup_email' => 'pilot@example.fr'])
        ->get('/inscription/confirmation');

    expect($response->viewData('page')['props']['email'])->toBe('pilot@example.fr');
});
