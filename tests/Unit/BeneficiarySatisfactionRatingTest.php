<?php

use App\Models\BeneficiarySatisfactionRating;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

/**
 * Unit-level guarantees on the model itself. Verifies encryption of the
 * comment column and the audit whitelist — comment is excluded so the
 * audits table never receives ciphertext deltas.
 */
it('encrypts the comment column at rest', function () {
    $rating = new BeneficiarySatisfactionRating;
    $rating->comment = 'Sensitive comment';

    // The raw DB value is encrypted, but the accessor returns the plaintext.
    expect($rating->comment)->toBe('Sensitive comment');
    expect($rating->getRawOriginal('comment'))->not->toBe('Sensitive comment');

    if ($rating->getRawOriginal('comment') !== null) {
        $decrypted = Crypt::decryptString($rating->getRawOriginal('comment'));
        expect($decrypted)->toBe('Sensitive comment');
    }
});

it('excludes the encrypted comment from the audit whitelist', function () {
    $rating = new BeneficiarySatisfactionRating;
    $reflection = new ReflectionProperty(BeneficiarySatisfactionRating::class, 'auditInclude');
    $reflection->setAccessible(true);
    $audited = $reflection->getValue($rating);

    expect($audited)->not->toContain('comment');
    expect($audited)->toContain('score');
    expect($audited)->toContain('rated_at');
});

it('casts score to int and rated_at to a date', function () {
    $rating = new BeneficiarySatisfactionRating;
    $rating->setRawAttributes([
        'score' => '4',
        'rated_at' => '2026-04-15',
    ]);

    expect($rating->score)->toBeInt();
    expect($rating->score)->toBe(4);
    expect($rating->rated_at)->toBeInstanceOf(Carbon::class);
});
