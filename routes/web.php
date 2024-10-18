<?php

use App\Http\Controllers\ShortLinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/links');
});

Route::get('/welcome', function () {
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
