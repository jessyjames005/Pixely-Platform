<?php

/**
 * ExamplePage public API routes.
 *
 * Registered by Core under the api/v1 prefix with the surface:api middleware.
 */

declare(strict_types=1);

use App\Extensions\ExamplePage\Http\Controllers\ExamplePageController;
use Illuminate\Support\Facades\Route;

/**
 * Public endpoint publishing the example page content.
 */
Route::get('/public/example-page', [ExamplePageController::class, 'show']);
