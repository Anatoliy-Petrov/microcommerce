<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('require.user_id')->group(function () {
    Route::get('/users/{id}', [ProfileController::class, 'show']);

    Route::middleware('owner')->group(function () {
        Route::get('/users/{id}/private',               [ProfileController::class, 'showPrivate']);
        Route::put('/users/{id}',                       [ProfileController::class, 'update']);

        Route::post('/users/{id}/avatar',               [AvatarController::class, 'store']);
        Route::delete('/users/{id}/avatar',             [AvatarController::class, 'destroy']);

        Route::get('/users/{id}/addresses',             [AddressController::class, 'index']);
        Route::post('/users/{id}/addresses',            [AddressController::class, 'store']);
        Route::put('/users/{id}/addresses/{addrId}',    [AddressController::class, 'update']);
        Route::delete('/users/{id}/addresses/{addrId}', [AddressController::class, 'destroy']);
    });
});
