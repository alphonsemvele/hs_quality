<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Smoke + behaviour tests for the public marketing pages.
 *
 * Coverage:
 *   - Every public page renders without a tenant context
 *   - Contact form validation rejects missing / invalid input
 *   - Valid contact submission is logged + flashes success
 *   - Rate limiter caps submissions per IP/email
 */
beforeEach(function (): void {
    // Wipe any rate-limit counters that previous tests may have hit.
    RateLimiter::clear('contact-form');
});

it('renders the landing page', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('index'));
});

it('renders the features page', function (): void {
    $this->get('/fonctionnalites')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('marketing/features'));
});

it('renders the compliance page', function (): void {
    $this->get('/conformite')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('marketing/compliance'));
});

it('renders the contact page', function (): void {
    $this->get('/contact')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('marketing/contact'));
});

it('renders all legal pages', function (string $url, string $component): void {
    $this->get($url)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with([
    'mentions légales' => ['/mentions-legales', 'marketing/legal-mentions'],
    'confidentialité' => ['/confidentialite', 'marketing/privacy'],
    'CGU' => ['/cgu', 'marketing/cgu'],
    'accessibilité' => ['/accessibilite', 'marketing/accessibility'],
]);

it('rejects an empty contact submission with field errors', function (): void {
    $this->from('/contact')
        ->post('/contact', [])
        ->assertRedirect('/contact')
        ->assertSessionHasErrors([
            'first_name',
            'last_name',
            'email',
            'structure_name',
            'structure_type',
            'team_size',
            'consent',
        ]);
});

it('rejects an invalid structure type', function (): void {
    $this->from('/contact')
        ->post('/contact', validContactPayload(['structure_type' => 'hospital']))
        ->assertSessionHasErrors(['structure_type']);
});

it('rejects a contact submission missing the consent checkbox', function (): void {
    $this->from('/contact')
        ->post('/contact', validContactPayload(['consent' => false]))
        ->assertSessionHasErrors(['consent']);
});

it('logs and flashes success on a valid contact submission', function (): void {
    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();
    Log::shouldReceive('info')
        ->once()
        ->with('contact.submission', Mockery::on(fn ($ctx) => $ctx['email'] === 'claire@saad-horizon.fr'
            && $ctx['structure_type'] === 'saad'));

    $this->from('/contact')
        ->post('/contact', validContactPayload([
            'email' => 'claire@saad-horizon.fr',
        ]))
        ->assertRedirect('/contact')
        ->assertSessionHas('success');
});

it('throttles contact submissions after 5 requests per IP', function (): void {
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('info');

    $payload = validContactPayload();

    for ($i = 0; $i < 5; $i++) {
        $this->post('/contact', array_merge($payload, [
            'email' => "lead{$i}@example.fr",
        ]))->assertRedirect('/contact');
    }

    // 6th submission from the same IP exceeds the per-IP limit (5/min).
    $this->post('/contact', array_merge($payload, [
        'email' => 'lead6@example.fr',
    ]))->assertStatus(429);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validContactPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Claire',
        'last_name' => 'Bernard',
        'email' => 'claire@saad-horizon.fr',
        'phone' => '0123456789',
        'structure_name' => 'SAAD Horizon',
        'structure_type' => 'saad',
        'team_size' => '11-50',
        'message' => 'Nous souhaitons piloter HS Quality.',
        'consent' => true,
    ], $overrides);
}
