<?php

use App\Http\Controllers\ShortLinkController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/links');
});

// Polymarket bot control via Telegram. The {secret} segment authenticates the
// caller; see config/polymarket.php (telegram.webhook_secret).
Route::post('/telegram/webhook/{secret}', TelegramWebhookController::class);

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
