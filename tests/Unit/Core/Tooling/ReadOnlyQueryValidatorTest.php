<?php

declare(strict_types=1);

use App\Core\Tooling\Services\ReadOnlyQueryValidator;

beforeEach(function () {
    $this->validator = new ReadOnlyQueryValidator();
});

it('accepts a simple SELECT', function () {
    $this->validator->assertSafe('SELECT * FROM users');
    expect(true)->toBeTrue();
});

it('accepts a SELECT with a trailing semicolon', function () {
    $this->validator->assertSafe('SELECT * FROM users;');
    expect(true)->toBeTrue();
});

it('rejects an empty query', function () {
    $this->validator->assertSafe('');
})->throws(InvalidArgumentException::class, 'The query cannot be empty.');

it('rejects a query not starting with SELECT', function () {
    $this->validator->assertSafe('DELETE FROM users');
})->throws(InvalidArgumentException::class, 'Only SELECT statements are allowed.');

it('rejects stacked statements', function () {
    $this->validator->assertSafe('SELECT * FROM users; DELETE FROM users;');
})->throws(InvalidArgumentException::class, 'Only a single statement is allowed.');

it('rejects a SELECT containing an INSERT keyword in a subquery-like string', function () {
    $this->validator->assertSafe('SELECT * FROM users WHERE id IN (INSERT INTO x VALUES (1))');
})->throws(InvalidArgumentException::class);

it('rejects querying forbidden tables', function () {
    $this->validator->assertSafe('SELECT * FROM sessions');
})->throws(InvalidArgumentException::class, "Querying the 'sessions' table is not allowed.");

it('rejects a disguised statement using a SQL comment', function () {
    $this->validator->assertSafe("-- comment\nDELETE FROM users");
})->throws(InvalidArgumentException::class);

it('enforces a LIMIT when none is present', function () {
    $result = $this->validator->enforceLimit('SELECT * FROM users', 50);
    expect($result)->toBe('SELECT * FROM users LIMIT 50');
});

it('replaces an existing LIMIT with the enforced maximum', function () {
    $result = $this->validator->enforceLimit('SELECT * FROM users LIMIT 10000', 50);
    expect($result)->toBe('SELECT * FROM users LIMIT 50');
});

it('redacts sensitive columns from a row', function () {
    $row = ['id' => 1, 'email' => 'a@b.com', 'password' => 'hash', 'remember_token' => 'token'];

    $redacted = $this->validator->redactRow($row);

    expect($redacted)
        ->toHaveKeys(['id', 'email'])
        ->not->toHaveKeys(['password', 'remember_token']);
});
