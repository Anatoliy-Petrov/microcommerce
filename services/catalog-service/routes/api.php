<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/products/search',              [ProductController::class, 'search']);
Route::get('/products',                     [ProductController::class, 'index']);
Route::get('/products/{id}',                [ProductController::class, 'show']);
Route::get('/products/{id}/stock',          [StockController::class, 'show']);
Route::get('/categories',                   [CategoryController::class, 'index']);
Route::get('/categories/{id}/products',     [CategoryController::class, 'products']);

// Admin-only routes
Route::middleware(['require.user_id', 'admin'])->group(function () {
    Route::post('/products',                [ProductController::class, 'store']);
    Route::put('/products/{id}',            [ProductController::class, 'update']);
    Route::delete('/products/{id}',         [ProductController::class, 'destroy']);
    Route::post('/products/{id}/images',           [ImageController::class, 'store']);
    Route::delete('/products/{id}/images/{imageId}', [ImageController::class, 'destroy']);

    Route::post('/categories',              [CategoryController::class, 'store']);
    Route::put('/categories/{id}',          [CategoryController::class, 'update']);
    Route::delete('/categories/{id}',       [CategoryController::class, 'destroy']);
});