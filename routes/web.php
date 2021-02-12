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
    Route::get('/links/{link}/edit', [ShortLinkController::class, 'edit']);
    Route::get('/links/{link}/delete', [ShortLinkController::class, 'delete']);
    Route::post('/links', [ShortLinkController::class, 'store']);
    Route::patch('/links/{link}', [ShortLinkController::class, 'update']);
});

Auth::routes();

Route::get('/{code}', [ShortLinkController::class, 'resolve']);
