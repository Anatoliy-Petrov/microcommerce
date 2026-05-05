<?php

use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.user_id', 'owner'])->group(function () {
    Route::get('/carts/{userId}',                          [CartController::class, 'show']);
    Route::post('/carts/{userId}/items',                   [CartController::class, 'addItem']);
    Route::put('/carts/{userId}/items/{productId}',        [CartController::class, 'updateItem']);
    Route::delete('/carts/{userId}/items/{productId}',     [CartController::class, 'removeItem']);
    Route::delete('/carts/{userId}',                       [CartController::class, 'clear']);
    Route::post('/carts/{userId}/checkout',                [CartController::class, 'checkout']);
});
