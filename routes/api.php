<?php

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\UserController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

//Маршруты Юзера
Route::post('/register', [RegisterController::class, 'register'])->middleware('api');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');

// Личный кабинет пользователя
Route::prefix('user')->middleware('auth:sanctum')->group(function () {
    Route::get('/account', [UserController::class, 'account']);
    Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store']);
});

//Маршруты заказа Юзера
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users/me/orders', [OrderController::class, 'store']);
    Route::get('/users/me/orders', [OrderController::class, 'index']);
    Route::get('/users/me/orders/{order}', [OrderController::class, 'show']);
});


// Маршруты администратора
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/dashboard', function () {
        return response()->json(['message' => 'Добро пожаловать в админ-панель']);
    });


    // Управление заказами
    Route::apiResource('orders', \App\Http\Controllers\Admin\OrderController::class)
        ->only(['index', 'show']);
    Route::patch('orders/{order}/status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus']);



    Route::apiResource('products', \App\Http\Controllers\Admin\ProductController::class);
});

// Маршруты для Товаров (только чтение)
Route::apiResource('products', ProductController::class)->only(['index', 'show', 'store']);


// Маршруты для корзины
Route::middleware(['api', StartSession::class, AddQueuedCookiesToResponse::class])->group(function () {
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/add/{productId}', [CartController::class, 'add']);
        Route::put('/update/{productId}', [CartController::class, 'update']);
        Route::delete('/remove/{productId}', [CartController::class, 'remove']);
        Route::delete('/clear', [CartController::class, 'clear']);
        Route::get('/total', [CartController::class, 'getTotal']);
    });
});
