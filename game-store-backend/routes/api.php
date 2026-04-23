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
use App\Http\Controllers\SupportController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminGamesController;
use App\Http\Controllers\Admin\AdminNewsController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminSupportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- Аутентификация --- //
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/passwordless', [AuthController::class, 'sendLoginCode']);
    Route::post('/passwordless/login', [AuthController::class, 'loginWithCode']);
    Route::post('/forgot-password', [AuthController::class, 'sendPasswordResetCode']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/password', [AuthController::class, 'changePassword']);
        Route::get('/my-reviews', [AuthController::class, 'myReviews']);
        Route::post('/email-change/request', [AuthController::class, 'requestEmailChange']);
        Route::post('/email-change/confirm', [AuthController::class, 'confirmEmailChange']);
    });
});

// --- Поддержка --- //
Route::post('/support/send', [SupportController::class, 'send']);

// --- Публичный маршрут для синхронизации корзины --- //
Route::post('/cart/sync', [CartController::class, 'sync']);

// --- Публичный маршрут для сотрудников (About page) --- //
Route::get('/employees', [EmployeeController::class, 'index']);

// --- Админка (защищено) --- //
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/stats', function () {
        return response()->json([
            'users'           => \App\Models\User::count(),
            'games'           => \App\Models\Game::count(),
            'orders'          => \App\Models\Order::count(),
            'reviews'         => \App\Models\Review::count(),
            'revenue'         => (int) \App\Models\Order::sum('total'),
            'support_new'     => \App\Models\SupportTicket::where('status', 'new')->count(),
            'support_total'   => \App\Models\SupportTicket::count(),
        ]);
    });

    Route::get('/users', [AdminUserController::class, 'index']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);
    Route::put('/users/{id}', [AdminUserController::class, 'update']);          // обновить ФИО/email/телефон
    Route::put('/users/{id}/role', [AdminUserController::class, 'updateRole']); // сменить роль


    Route::get('/games', [AdminGamesController::class, 'index']);
    Route::get('/games/{game}', [AdminGamesController::class, 'show']);        // было {id}
    Route::post('/games', [AdminGamesController::class, 'store']);
    Route::put('/games/{game}', [AdminGamesController::class, 'update']);      // уже поправил правильно
    Route::delete('/games/{game}', [AdminGamesController::class, 'destroy']);  // было {id}
    Route::post('/games/{game}/gallery', [AdminGamesController::class, 'uploadGallery']);
    Route::delete('/games/{game}/gallery/{image}', [AdminGamesController::class, 'deleteGalleryImage']);
    
    // дальше остальные админские
    Route::get('/reviews', [AdminReviewController::class, 'index']);
    Route::put('/reviews/{id}', [AdminReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [AdminReviewController::class, 'destroy']);

    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::put('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);


    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::get('/employees/{employee}', [EmployeeController::class, 'show']);
    Route::put('/employees/{employee}', [EmployeeController::class, 'update']);
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy']);

    Route::get('/news', [AdminNewsController::class, 'index']);
    Route::post('/news', [AdminNewsController::class, 'store']);
    Route::get('/news/{news}', [AdminNewsController::class, 'show']);
    Route::put('/news/{news}', [AdminNewsController::class, 'update']);
    Route::delete('/news/{news}', [AdminNewsController::class, 'destroy']);

    Route::get('/support',        [AdminSupportController::class, 'index']);
    Route::put('/support/{id}',   [AdminSupportController::class, 'update']);
    Route::delete('/support/{id}',[AdminSupportController::class, 'destroy']);
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
    Route::post('/clear', [CartController::class, 'clear']);
});
