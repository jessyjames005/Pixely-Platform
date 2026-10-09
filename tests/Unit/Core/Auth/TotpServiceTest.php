<?php

declare(strict_types=1);

use App\Core\Auth\Services\TotpService;

// RFC 6238 appendix B shared secret ("12345678901234567890"), base32 encoded.
$rfcSecret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

it('matches the RFC 6238 SHA-1 test vectors', function (int $timestamp, string $expected) use ($rfcSecret) {
    $totp = new TotpService();

    expect($totp->codeAt($rfcSecret, $totp->timeStep($timestamp)))->toBe($expected);
})->with([
    'T=59' => [59, '287082'],
    'T=1111111109' => [1111111109, '081804'],
    'T=1111111111' => [1111111111, '050471'],
    'T=1234567890' => [1234567890, '005924'],
    'T=2000000000' => [2000000000, '279037'],
    'T=20000000000' => [20000000000, '353130'],
]);

it('round-trips base32', function () use ($rfcSecret) {
    $totp = new TotpService();

    expect($totp->base32Encode('12345678901234567890'))->toBe($rfcSecret)
        ->and($totp->base32Decode($rfcSecret))->toBe('12345678901234567890')
        ->and($totp->base32Decode(strtolower($rfcSecret) . '===='))->toBe('12345678901234567890');
});

it('rejects characters outside the base32 alphabet', function () {
    (new TotpService())->base32Decode('NOT-BASE32!');
})->throws(InvalidArgumentException::class);

it('generates a 32 character base32 secret', function () {
    $secret = (new TotpService())->generateSecret();

    expect($secret)->toMatch('/^[A-Z2-7]{32}$/');
});

it('accepts the current time step and one step of clock drift either side', function () use ($rfcSecret) {
    $totp = new TotpService();

    // Code for step 1 (timestamps 30-59), checked at step 1, 2 and 0.
    expect($totp->verify($rfcSecret, '287082', null, 59))->toBe(1)
        ->and($totp->verify($rfcSecret, '287082', null, 89))->toBe(1)
        ->and($totp->verify($rfcSecret, '287082', null, 29))->toBe(1);
});

it('rejects a code that is outside the drift window', function () use ($rfcSecret) {
    $totp = new TotpService();

    // Step 1 code checked at step 3: window covers steps 2-4 only.
    expect($totp->verify($rfcSecret, '287082', null, 119))->toBeNull();
});

it('rejects a step that was already used (replay protection)', function () use ($rfcSecret) {
    $totp = new TotpService();

    expect($totp->verify($rfcSecret, '287082', 1, 59))->toBeNull()
        ->and($totp->verify($rfcSecret, '287082', 0, 59))->toBe(1);
});

it('ignores whitespace and rejects malformed codes', function () use ($rfcSecret) {
    $totp = new TotpService();

    expect($totp->verify($rfcSecret, '287 082', null, 59))->toBe(1)
        ->and($totp->verify($rfcSecret, '28708', null, 59))->toBeNull()
        ->and($totp->verify($rfcSecret, '2870822', null, 59))->toBeNull()
        ->and($totp->verify($rfcSecret, 'abcdef', null, 59))->toBeNull()
        ->and($totp->verify($rfcSecret, '', null, 59))->toBeNull();
});

it('builds an otpauth provisioning URI', function () use ($rfcSecret) {
    $uri = (new TotpService())->provisioningUri($rfcSecret, 'jane@example.com', 'Pixely');

    expect($uri)->toBe(
        'otpauth://totp/Pixely:jane%40example.com'
        . '?secret=' . $rfcSecret
        . '&issuer=Pixely&algorithm=SHA1&digits=6&period=30',
    );
});
