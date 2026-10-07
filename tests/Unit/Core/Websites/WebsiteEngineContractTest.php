<?php

declare(strict_types=1);

use App\Core\Websites\Contracts\WebsiteEngineInterface;
use App\Core\Websites\Services\WebsiteEngine;

it('binds the website engine implementation to its contract', function (): void {
    expect(app(WebsiteEngineInterface::class))->toBeInstanceOf(WebsiteEngine::class);
});
