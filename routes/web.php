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

Route::get('/assets/static-map/{lat}/{lon}/{text}.png', [\App\Http\Controllers\PageController::class, 'tripleZoomMap'] )
    ->name('tripleZoomMap');
Route::get('/{slug}', [\App\Http\Controllers\PageController::class, 'page']);
