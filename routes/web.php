<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('orders.create'));

Route::get('/order',    [OrderController::class,   'create'])->name('orders.create');
Route::post('/order',   [OrderController::class,   'store'])->name('orders.store');
Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');
