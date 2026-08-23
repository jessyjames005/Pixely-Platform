<?php

declare(strict_types=1);

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Pest Test Configuration
|--------------------------------------------------------------------------
|
| Laravel feature tests use the application's base TestCase.
| This configuration applies the Laravel test case to all tests
| located inside the Feature directory.
|
*/

uses(TestCase::class)
    ->in('Feature');
