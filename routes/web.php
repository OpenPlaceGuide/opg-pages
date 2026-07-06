<?php

use App\Http\Controllers\CacheController;
use App\Http\Controllers\DetailRedirectController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/assets/static-map/{lat}/{lon}/{slug}.png', [\App\Http\Controllers\PageController::class, 'tripleZoomMap'] )
    ->name('tripleZoomMap');

// Footer "Refresh data" button: POST (so crawlers never trigger it) that
// redirects back to the page with a cache-busting `refresh-cache` param.
Route::post('/refresh-cache', [CacheController::class, 'refresh'])
    ->name('refreshCache');

// Lazy-loaded, per-branch fragments (Mapillary images / Mangrove reviews).
// Same public cache headers as the pages, so responses are full-page cached.
Route::middleware(\App\Services\Cache::getCacheMiddleware())
    ->group(function() {
        Route::get('/api/branch/{lat}/{lon}/mapillary', [\App\Http\Controllers\BranchDataController::class, 'mapillary'])
            ->name('branch.mapillary');
        Route::get('/api/branch/{lat}/{lon}/mangrove', [\App\Http\Controllers\BranchDataController::class, 'mangrove'])
            ->name('branch.mangrove');
    });

$routes = function($locale) {
    Route::get('/{osmTypeLetter}{osmId}/{slug?}', [\App\Http\Controllers\PageController::class, 'osmPlace'])
        ->where('osmTypeLetter', '[nwr]')
        ->where('osmId', '[0-9]*')
        ->name('osmPlace' . '.' . $locale);

    Route::get('/{slug}', [\App\Http\Controllers\PageController::class, 'page'])
        ->where('slug', '[a-z-]{3,}')
        ->name('page' . '.' . $locale);

    // Must be registered before the catch-all /{areaSlug}/{typeSlug} route.
    // Cached for 5 minutes instead of the group's 24 hours so new OSM edits
    // show up quickly without hitting Overpass on every view.
    Route::get('/{areaSlug}/newsfeed', [\App\Http\Controllers\PageController::class, 'newsfeed'])
        ->where('areaSlug', '[a-z-]{3,}')
        ->withoutMiddleware(\App\Services\Cache::getCacheMiddleware())
        ->middleware(\App\Services\Cache::getCacheMiddleware(\App\Services\Cache::SHORT_LIFETIME))
        ->name('newsfeed' . '.' . $locale);

    Route::get('/{areaSlug}/{typeSlug}', [\App\Http\Controllers\PageController::class, 'typePage'])
        ->where('typeSlug', '[a-z-]{3,}')
        ->where('areaSlug', '[a-z-]{3,}')
        ->name('typesInArea' . '.' . $locale);
};

foreach(config('app.additional_locales') as $locale) {
    Route::middleware(\App\Services\Cache::getCacheMiddleware())
        ->prefix($locale. '/')->group(function() use ($locale, $routes) { $routes($locale); });
}

Route::middleware(\App\Services\Cache::getCacheMiddleware())
    ->group(function() use ($routes) { $routes(config('app.locale')); });

Route::get('/detail/node/{osmId}', [DetailRedirectController::class, 'node'])
    ->where('osmId', '[0-9]*');
Route::get('/detail/way/{osmId}', [DetailRedirectController::class, 'way'])
    ->where('osmId', '[0-9]*');
Route::get('/detail/relation/{osmId}', [DetailRedirectController::class, 'relation'])
    ->where('osmId', '[0-9]*');

Route::middleware(\App\Services\Cache::getCacheMiddleware())
    ->group(function() {
        Route::get('/sitemap.xml', [SitemapController::class, 'index']);
});
