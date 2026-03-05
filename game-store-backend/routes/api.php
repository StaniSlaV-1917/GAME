<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\GameImageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Admin\AdminGameController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminImageController;
use App\Http\Controllers\Admin\AdminReportController;
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
|
| Требует middleware 'manager':
|  - role = manager или admin
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
|
| Требует middleware 'admin':
|  - role = admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {
        // Отчёты / экспорт
        Route::get('/reports/orders', [AdminReportController::class, 'exportOrders']);
        Route::get('/reports/users', [AdminReportController::class, 'exportUsers']);
        Route::get('/reports/games', [AdminReportController::class, 'exportGames']);

        // Игры: полный CRUD
        Route::get('/games', [AdminGameController::class, 'index']);
        Route::post('/games', [AdminGameController::class, 'store']);
        Route::put('/games/{id}', [AdminGameController::class, 'update']);
        Route::delete('/games/{id}', [AdminGameController::class, 'destroy']);

        // Галерея игр (контроллер GameImageController в корне Controllers)
        Route::get('/games/{game}/images', [GameImageController::class, 'index']);
        Route::post('/games/{game}/images', [GameImageController::class, 'store']);
        Route::put('/games/{game}/images/{image}', [GameImageController::class, 'update']);
        Route::delete('/games/{game}/images/{image}', [GameImageController::class, 'destroy']);
        Route::get('/image-library', [ImageLibraryController::class, 'index']);

        // Управление ролями пользователей
        Route::put('/users/{id}/role', [AdminUserController::class, 'updateRole']);

        // Отзывы
        Route::get('/reviews', [AdminReviewController::class, 'index']);
        Route::put('/reviews/{id}', [AdminReviewController::class, 'update']);
        Route::delete('/reviews/{id}', [AdminReviewController::class, 'destroy']);
        Route::post('/reviews/{id}/restore', [AdminReviewController::class, 'restore']);

         Route::get('/employees', [EmployeeController::class, 'index']);      // список сотрудников
        Route::post('/employees', [EmployeeController::class, 'store']);      // создать сотрудника
        Route::get('/employees/{employee}', [EmployeeController::class, 'show']);   // карточка сотрудника
        Route::put('/employees/{employee}', [EmployeeController::class, 'update']); // редактировать
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy']); // удалить

        // если нужно, можно добавить отдельные роуты, например:
        Route::put('/employees/{employee}/role', [EmployeeController::class, 'updateRole']); // смена роли
    });
