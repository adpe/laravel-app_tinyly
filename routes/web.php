<?php

use App\Http\Controllers\ShortLinkController;
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

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/links');
    }

    return view('welcome');
});

Route::group(['middleware' => 'auth'], function () {
    Route::get('/links', [ShortLinkController::class, 'show']);
    Route::get('/links/create', [ShortLinkController::class, 'create']);
    Route::get('/links/{link}/edit', [ShortLinkController::class, 'edit']);
    Route::get('/links/{link}/delete', [ShortLinkController::class, 'delete']);
    Route::post('/links', [ShortLinkController::class, 'store']);
    Route::patch('/links/{link}', [ShortLinkController::class, 'update']);
});

Auth::routes([
    'register' => env('AUTH_REGISTER', true),
]);

Route::get('/{code}', [ShortLinkController::class, 'resolve']);
