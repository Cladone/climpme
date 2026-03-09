<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\IngestTokenController;  // <-- API (ALL CAPS)
use App\Http\Controllers\API\V1\TrafficController;      // do the same for TrafficController for consistency

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| These routes are loaded by the RouteServiceProvider within the "api"
| middleware group.
|
| Strategy (Option B):
| - Traffic endpoints (ingest-token, traffic) are NOT behind auth:api.
|   * ingest-token → protected by your custom api.guard (API key auth)
|   * traffic      → protected by auth.ingestjwt (short-lived JWT)
| - Legacy resources remain behind auth:api and api.guard as before.
*/

Route::prefix('v1')->middleware(['throttle:120'])->group(function () {
    // -------------------------
    // Traffic (no auth:api here)
    // -------------------------

    // Issue short-lived ingest JWT (scope: traffic:write)
    Route::post('ingest-token', [IngestTokenController::class, 'issue'])
        ->middleware('api.guard') // your API-key auth (accepts Bearer / X-Api-Key / ?api_key=...)
        ->name('api.ingest-token.issue');

    // Front-end beacon ingestion (uses the short-lived JWT from ingest-token)
    Route::post('traffic', [TrafficController::class, 'ingest'])
        ->middleware('auth.ingestjwt') // your JWT middleware
        ->name('api.traffic.ingest');

    // ------------------------------------
    // Legacy resources behind auth:api
    // ------------------------------------
    Route::middleware('auth:api')->group(function () {

        Route::apiResource('links', 'API\LinkController', [
            'parameters' => ['links' => 'id'],
            'as' => 'api',
        ])->middleware('api.guard');

        Route::apiResource('domains', 'API\DomainController', [
            'parameters' => ['domains' => 'id'],
            'as' => 'api',
        ])->middleware('api.guard');

        Route::apiResource('spaces', 'API\SpaceController', [
            'parameters' => ['spaces' => 'id'],
            'as' => 'api',
        ])->middleware('api.guard');

        Route::apiResource('pixels', 'API\PixelController', [
            'parameters' => ['pixels' => 'id'],
            'as' => 'api',
        ])->middleware('api.guard');

        Route::apiResource('stats', 'API\StatController', [
            'parameters' => ['stats' => 'id'],
            'only' => ['show'],
            'as' => 'api',
        ])->middleware('api.guard');

        Route::apiResource('account', 'API\AccountController', [
            'only' => ['index'],
            'as' => 'api',
        ])->middleware('api.guard');
    });

    // ---------------
    // API Fallback
    // ---------------
    Route::fallback(function () {
        return response()->json(['message' => __('Resource not found.'), 'status' => 404], 404);
    });
});
