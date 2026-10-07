<?php

declare(strict_types=1);

use App\Core\Surface\Services\SurfaceContext;
use Illuminate\Support\Facades\Route;

it('establishes the public surface for a public route', function () {
    Route::middleware('surface:public')->get('/__surface/public', function (SurfaceContext $context) {
        return response()->json(['surface' => $context->current()->value]);
    });

    $this->getJson('/__surface/public')
        ->assertOk()
        ->assertJsonPath('surface', 'public');
});

it('rejects an unknown surface before entering the controller', function () {
    Route::middleware('surface:unknown')->get('/__surface/invalid', fn () => response()->noContent());

    $this->get('/__surface/invalid')->assertStatus(500);
});
