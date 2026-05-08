<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::get('/products/search',          [ProductController::class, 'search']);
Route::get('/products',                 [ProductController::class, 'index']);
Route::get('/products/{id}',            [ProductController::class, 'show']);
Route::get('/products/{id}/stock',      [StockController::class, 'show']);
Route::get('/categories',               [CategoryController::class, 'index']);
Route::get('/categories/{id}/products', [CategoryController::class, 'products']);