<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves public navigation without authentication', function (): void {
    $response = $this->getJson('/api/v1/website/navigation/main');

    $response->assertOk()->assertJsonPath('data', []);
});
