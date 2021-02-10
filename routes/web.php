<?php

use App\Http\Controllers\ShortLinkController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/links');
});

Route::group(['middleware' => 'auth'], function () {
    Route::get('/links', [ShortLinkController::class, 'show']);
    Route::get('/links/create', [ShortLinkController::class, 'create']);
    Route::post('/links/create', [ShortLinkController::class, 'store']);
    Route::get('/links/{link}/delete', [ShortLinkController::class, 'delete']);
});

Auth::routes();

Route::get('/{code}', [ShortLinkController::class, 'resolve']);
