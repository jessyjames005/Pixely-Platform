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
| located inside the Feature directory, and to the Unit suites that
| need the booted application (container, Gate, database): the Surface
| and Websites unit tests.
|
*/

uses(TestCase::class)
    ->in('Feature', 'Unit/Core/Surface', 'Unit/Core/Websites');
