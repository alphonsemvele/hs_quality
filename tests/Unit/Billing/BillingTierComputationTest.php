<?php

use App\Enums\StructureTier;

it('returns the correct monthly price per user for each tier', function () {
    expect(StructureTier::Essential->monthlyPricePerUser())->toBe(8);
    expect(StructureTier::Pro->monthlyPricePerUser())->toBe(15);
    expect(StructureTier::Premium->monthlyPricePerUser())->toBe(25);
});

it('returns French labels for each tier', function () {
    expect(StructureTier::Essential->label())->toBe('Essentiel');
    expect(StructureTier::Pro->label())->toBe('Pro');
    expect(StructureTier::Premium->label())->toBe('Premium');
});

it('computes total monthly billing as price * users', function () {
    $userCount = 12;

    $totals = [
        'essential' => StructureTier::Essential->monthlyPricePerUser() * $userCount,
        'pro' => StructureTier::Pro->monthlyPricePerUser() * $userCount,
        'premium' => StructureTier::Premium->monthlyPricePerUser() * $userCount,
    ];

    expect($totals)->toEqual([
        'essential' => 96,
        'pro' => 180,
        'premium' => 300,
    ]);
});

it('enumerates all tiers in ascending price order', function () {
    $tiers = StructureTier::cases();
    $prices = array_map(fn ($t) => $t->monthlyPricePerUser(), $tiers);

    expect($prices)->toBe([8, 15, 25]);
});
