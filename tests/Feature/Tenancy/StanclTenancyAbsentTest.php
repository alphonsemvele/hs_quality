<?php

declare(strict_types=1);
use Stancl\Tenancy\TenancyServiceProvider;

/**
 * stancl/tenancy is intentionally NOT installed — this project uses a custom
 * row-level tenancy via the BelongsToStructure trait + global scope. The
 * Stancl package was previously installed unused and registered a public
 * file-serving route at GET /tenancy/assets/{path?} that bypassed our
 * tenancy chain entirely.
 *
 * If this test fails, the package was reinstalled by mistake (likely via a
 * "did you mean" composer suggestion). Remove it again with
 * `composer remove stancl/tenancy` before continuing.
 */
it('does not have stancl/tenancy installed', function (): void {
    expect(class_exists(TenancyServiceProvider::class))->toBeFalse();
});

it('does not expose /tenancy/assets/{path?}', function (): void {
    $this->get('/tenancy/assets/test.png')->assertNotFound();
});
