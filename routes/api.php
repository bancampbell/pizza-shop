<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

//Маршруты Юзера
Route::post('/register', [RegisterController::class, 'register'])->middleware('api');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');


// Маршруты для Товаров
Route::apiResource('products', ProductController::class);


// Маршруты для корзины
Route::middleware(['api', StartSession::class, AddQueuedCookiesToResponse::class])->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add/{productId}', [CartController::class, 'add'])->middleware('auth:sanctum');
    Route::delete('/cart/remove/{productId}', [CartController::class, 'remove']);
    Route::delete('/cart/clear', [CartController::class, 'clear']);
});
