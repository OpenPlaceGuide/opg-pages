<?php

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

$routes = function($locale) {
    Route::get('/{osmTypeLetter}{osmId}/{slug?}', [\App\Http\Controllers\PageController::class, 'osmPlace'])
        ->where('osmTypeLetter', '[nwr]')
        ->where('osmId', '[0-9]*')
        ->name('osmPlace' . '.' . $locale);

    Route::get('/{slug}', [\App\Http\Controllers\PageController::class, 'page'])
        ->where('slug', '[a-z-]{3,}')
        ->name('page' . '.' . $locale);

    Route::get('/{areaSlug}/{typeSlug}', [\App\Http\Controllers\PageController::class, 'typePage'])
        ->where('typeSlug', '[a-z-]{3,}')
        ->where('areaSlug', '[a-z-]{3,}')
        ->name('typesInArea' . '.' . $locale);
};

foreach(config('app.additional_locales') as $locale) {
    Route::prefix($locale. '/')->group(function() use ($locale, $routes) { $routes($locale); });
}

$routes(config('app.locale'));
