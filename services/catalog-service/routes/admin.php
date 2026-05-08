<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ImageController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.user_id', 'admin'])->group(function () {
    Route::post('/products',                         [ProductController::class, 'store']);
    Route::put('/products/{id}',                     [ProductController::class, 'update']);
    Route::delete('/products/{id}',                  [ProductController::class, 'destroy']);

    Route::post('/products/{id}/images',             [ImageController::class, 'store']);
    Route::delete('/products/{id}/images/{imageId}', [ImageController::class, 'destroy']);

    Route::post('/categories',                       [CategoryController::class, 'store']);
    Route::put('/categories/{id}',                   [CategoryController::class, 'update']);
    Route::delete('/categories/{id}',                [CategoryController::class, 'destroy']);
});