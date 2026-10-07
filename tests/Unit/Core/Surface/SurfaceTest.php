<?php

declare(strict_types=1);

use App\Core\Surface\Enum\Surface;
use App\Core\Surface\Services\SurfaceContext;
use App\Core\Surface\Services\SurfaceResolver;
use Illuminate\Http\Request;

it('supports the four platform surfaces', function () {
    expect(Surface::cases())->toHaveCount(4)
        ->and(Surface::fromValue('public'))->toBe(Surface::PUBLIC)
        ->and(Surface::fromValue('user'))->toBe(Surface::USER)
        ->and(Surface::fromValue('admin'))->toBe(Surface::ADMIN)
        ->and(Surface::fromValue('api'))->toBe(Surface::API);
});

it('stores and exposes the current surface in the request context', function () {
    $context = new SurfaceContext();

    $context->set(Surface::ADMIN);

    expect($context->has())->toBeTrue()
        ->and($context->current())->toBe(Surface::ADMIN)
        ->and($context->is(Surface::ADMIN))->toBeTrue()
        ->and($context->is(Surface::USER))->toBeFalse();
});

it('resolves a surface from the request attribute', function () {
    $request = Request::create('/admin');
    $request->attributes->set('pixely.surface', 'admin');

    expect((new SurfaceResolver())->fromRequest($request))->toBe(Surface::ADMIN);
});
