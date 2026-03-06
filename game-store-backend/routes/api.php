<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminGamesController;
use App\Http\Controllers\Admin\AdminNewsController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminReviewController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- Аутентификация --- //
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']); 
        Route::post('/password', [AuthController::class, 'changePassword']);
        Route::get('/my-reviews', [AuthController::class, 'myReviews']);
    });
});

// --- Публичный маршрут для синхронизации корзины --- //
Route::post('/cart/sync', [CartController::class, 'sync']);

// --- Админка (защищено) --- //
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

    Route::get('/games', [AdminGamesController::class, 'index']);
    Route::get('/games/{id}', [AdminGamesController::class, 'show']);
    Route::post('/games', [AdminGamesController::class, 'store']);
    Route::put('/games/{id}', [AdminGamesController::class, 'update']);
    Route::delete('/games/{id}', [AdminGamesController::class, 'destroy']);
    Route::post('/games/{game}/gallery', [AdminGamesController::class, 'uploadGallery']);
    Route::delete('/games/{game}/gallery/{image}', [AdminGamesController::class, 'deleteGalleryImage']);

    Route::get('/reviews', [AdminReviewController::class, 'index']);
    Route::put('/reviews/{id}', [AdminReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [AdminReviewController::class, 'destroy']);

    Route::get('/orders', [AdminOrderController::class, 'index']);

    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::get('/employees/{id}', [EmployeeController::class, 'show']);
    Route::put('/employees/{id}', [EmployeeController::class, 'update']);
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);

    Route::get('/news', [AdminNewsController::class, 'index']);
    Route::post('/news', [AdminNewsController::class, 'store']);
    Route::get('/news/{id}', [AdminNewsController::class, 'show']);
    Route::put('/news/{id}', [AdminNewsController::class, 'update']);
    Route::delete('/news/{id}', [AdminNewsController::class, 'destroy']);
});


// --- Основные публичные маршруты --- //

// Игры
Route::get('/games', [GameController::class, 'index']);
Route::get('/games/{id}', [GameController::class, 'show']);
Route::get('/genres', [GameController::class, 'getGenres']);

// Новости
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{id}', [NewsController::class, 'show']);

// Отзывы для игры
Route::get('/games/{gameId}/reviews', [ReviewController::class, 'index']);
Route::middleware('auth:sanctum')->post('/games/{gameId}/reviews', [ReviewController::class, 'store']);

// Оформление заказа
Route::middleware('auth:sanctum')->post('/orders', [OrderController::class, 'store']);
Route::middleware('auth:sanctum')->get('/orders', [OrderController::class, 'index']);


// --- Корзина (требует авторизации и сессии) --- //
Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/add', [CartController::class, 'add']);
    Route::post('/update', [CartController::class, 'update']);
    Route::post('/remove', [CartController::class, 'remove']);
});

