<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\GameImageController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Admin\AdminGameController;
use App\Http\Controllers\Admin\AdminNewsController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminImageController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\HomePageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageLibraryController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\EmployeeController;

/*
|--------------------------------------------------------------------------
| Публичные роуты (без авторизации)
|--------------------------------------------------------------------------
*/

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/games', [GameController::class, 'index']);
Route::get('/games/{id}', [GameController::class, 'show']);

Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{id}', [NewsController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Общие защищённые роуты (любой авторизованный пользователь)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'changePassword']);

    // Корзина
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::post('/cart/remove', [CartController::class, 'remove']);
    Route::post('/cart/update', [CartController::class, 'update']);

    // Заказы (для пользователя — свои заказы)
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);

    // Отзывы к играм
    Route::post('/games/{game}/reviews', [ReviewController::class, 'store']);

    Route::get('/auth/my-reviews', [AuthController::class, 'myReviews']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'changePassword']);

});

/*
|--------------------------------------------------------------------------
| Менеджер + админ (расширенный доступ)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'manager'])
    ->prefix('manager')
    ->group(function () {
        // Просмотр всех заказов, изменение статусов
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::put('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);

        // Просмотр и редактирование пользователей (без смены ролей)
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::put('/users/{id}', [AdminUserController::class, 'update']);
    });

/*
|--------------------------------------------------------------------------
| Только админ (полный доступ)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {
        // Редактор главной страницы
        Route::get('/home/editor', [HomePageController::class, 'getEditorData']);
        // Отчёты / экспорт
        Route::get('/reports/orders', [AdminReportController::class, 'exportOrders']);
        Route::get('/reports/users', [AdminReportController::class, 'exportUsers']);
        Route::get('/reports/games', [AdminReportController::class, 'exportGames']);

        // Игры: полный CRUD
        Route::get('/games', [AdminGameController::class, 'index']);
        Route::get('/games/{id}', [AdminGameController::class, 'show']); // ПОЛУЧЕНИЕ ОДНОЙ ИГРЫ
        Route::post('/games', [AdminGameController::class, 'store']);
        // Для обновления используется POST для корректной отправки файлов (multipart/form-data)
        Route::post('/games/{id}', [AdminGameController::class, 'update']);
        Route::delete('/games/{id}', [AdminGameController::class, 'destroy']);

        // Новости: полный CRUD
        Route::apiResource('/news', AdminNewsController::class);

        // Удаление одного изображения из галереи
        Route::delete('/games/images/{imageId}', [AdminGameController::class, 'destroyImage']);

        // Управление ролями пользователей
        Route::put('/users/{id}/role', [AdminUserController::class, 'updateRole']);

        // Отзывы
        Route::get('/reviews', [AdminReviewController::class, 'index']);
        Route::put('/reviews/{id}', [AdminReviewController::class, 'update']);
        Route::delete('/reviews/{id}', [AdminReviewController::class, 'destroy']);
        Route::post('/reviews/{id}/restore', [AdminReviewController::class, 'restore']);

         Route::get('/employees', [EmployeeController::class, 'index']);
        Route::post('/employees', [EmployeeController::class, 'store']);
        Route::get('/employees/{employee}', [EmployeeController::class, 'show']);
        Route::put('/employees/{employee}', [EmployeeController::class, 'update']);
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy']);

        Route::put('/employees/{employee}/role', [EmployeeController::class, 'updateRole']);
    });
