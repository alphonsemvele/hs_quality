<?php

declare(strict_types=1);

use App\Features\QvctWeakSignalAlerts;
use App\Models\Structure;

it('resolves to true by default — flag is on for every structure', function (): void {
    // Pure unit test: resolve() is invariant of the structure's persisted
    // state. The flag default is "on"; per-structure deactivation is
    // exercised through Pennant in the matching feature test.
    $structure = new Structure;

    expect((new QvctWeakSignalAlerts)->resolve($structure))->toBeTrue();
});
