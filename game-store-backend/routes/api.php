<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\UserProfileController;
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
use App\Http\Controllers\Admin\AdminModsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- Аутентификация --- //
Route::prefix('auth')->group(function () {
    // ── Phase 1.5 защита от ботов и brute-force ──
    // Стратегия:
    //   • Turnstile на ВСЕХ публичных auth-эндпоинтах (вариант A).
    //   • Throttle 3/мин — на отправку email-кодов (предотвращает email-spam).
    //   • Throttle 5/мин — на сабмит креденшелов (предотвращает brute-force).
    // Throttle bind по умолчанию = IP. Этого достаточно: бот с IP-ротацией
    // упрётся в Turnstile, бот без — в throttle. Для real-юзера лимит
    // практически недостижим.

    // Регистрация и логин-сабмиты (включая брутфорс пароля и кода)
    Route::middleware(['turnstile', 'throttle:5,1'])->group(function () {
        Route::post('/register',           [AuthController::class, 'register']);
        Route::post('/login',              [AuthController::class, 'login']);
        Route::post('/passwordless/login', [AuthController::class, 'loginWithCode']);
        Route::post('/reset-password',     [AuthController::class, 'resetPassword']);
    });

    // Запрос отправки кода на email — мягче лимит, тк для отправки одного
    // email достаточно одного запроса; цель — анти-email-spam.
    Route::middleware(['turnstile', 'throttle:3,1'])->group(function () {
        Route::post('/passwordless',     [AuthController::class, 'sendLoginCode']);
        Route::post('/forgot-password',  [AuthController::class, 'sendPasswordResetCode']);
    });

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
// Публичный эндпоинт без auth — самая «ботоопасная» дыра. Turnstile
// + throttle 3/мин по IP перекрывает спам-тикеты.
Route::middleware(['turnstile', 'throttle:3,1'])
    ->post('/support/send', [SupportController::class, 'send']);

// --- Публичный маршрут для синхронизации корзины --- //
// Мягкий throttle 30/мин — синхронизация может вызываться при каждом
// изменении корзины, реальный юзер 30 раз в минуту не достигнет.
Route::middleware('throttle:30,1')->post('/cart/sync', [CartController::class, 'sync']);

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

    // Phase 1.6/B — админ только модерирует, личные данные не правит.
    // Ранее был PUT /users/{id} для редактирования fullname/email/phone
    // — удалён намеренно. Личные данные пользователь меняет сам через
    // /api/auth/profile.
    Route::get('/users',                        [AdminUserController::class, 'index']);
    Route::put('/users/{id}/role',              [AdminUserController::class, 'updateRole']);
    Route::post('/users/{id}/ban',              [AdminUserController::class, 'ban']);
    Route::post('/users/{id}/unban',            [AdminUserController::class, 'unban']);
    Route::post('/users/{id}/freeze',           [AdminUserController::class, 'freeze']);
    Route::post('/users/{id}/unfreeze',         [AdminUserController::class, 'unfreeze']);
    Route::delete('/users/{id}',                [AdminUserController::class, 'destroy']);


    Route::get('/games', [AdminGamesController::class, 'index']);
    Route::get('/games/{game}', [AdminGamesController::class, 'show']);        // было {id}
    Route::post('/games', [AdminGamesController::class, 'store']);
    Route::put('/games/{game}', [AdminGamesController::class, 'update']);      // уже поправил правильно
    Route::delete('/games/{game}', [AdminGamesController::class, 'destroy']);  // было {id}
    Route::post('/games/{game}/gallery', [AdminGamesController::class, 'uploadGallery']);
    Route::delete('/games/{game}/gallery/{image}', [AdminGamesController::class, 'deleteGalleryImage']);

    // Моды для игр
    Route::get('/games/{game}/mods', [AdminModsController::class, 'index']);
    Route::get('/games/{game}/mods/{mod}', [AdminModsController::class, 'show']);
    Route::post('/games/{game}/mods', [AdminModsController::class, 'store']);
    Route::put('/games/{game}/mods/{mod}', [AdminModsController::class, 'update']);
    Route::delete('/games/{game}/mods/{mod}', [AdminModsController::class, 'destroy']);
    
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
Route::get('/games/{gameId}/mods', [GameController::class, 'getMods']);

// Новости
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{id}', [NewsController::class, 'show']);

// ── Phase 2 / Forum: посты ──
// Публичные read — лента и одиночный пост (гости тоже читают).
Route::get('/posts',      [PostController::class, 'index']);
Route::get('/posts/{id}', [PostController::class, 'show']);

// ── Phase 3 / Forum: персонализированная лента ──
// Auth optional: для гостей trending, для логинутых — followers feed.
Route::get('/feed', [PostController::class, 'feed']);

// CRUD — auth + throttle. Frozen-юзеры получают 403 (внутри контроллера).
// throttle:5,1 — 5 действий/мин по user_id. Анти-spam постов.
Route::middleware(['auth:sanctum', 'throttle:5,1'])->group(function () {
    Route::post('/posts',                [PostController::class, 'store']);
    Route::put('/posts/{id}',            [PostController::class, 'update']);
    Route::delete('/posts/{id}',         [PostController::class, 'destroy']);
    Route::post('/posts/upload-cover',   [PostController::class, 'uploadCover']);
});

// ── Phase 2 / Forum: комментарии ──
// Read — публичный (для гостей, чтобы могли смотреть дискуссию).
// Write — auth + throttle:10,1 (мягче чем посты, тк комменты короче и
// чаще; 10/мин достаточно для нормальной дискуссии).
Route::get('/posts/{postId}/comments', [CommentController::class, 'index']);
Route::middleware(['auth:sanctum', 'throttle:10,1'])->group(function () {
    Route::post('/posts/{postId}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{id}',            [CommentController::class, 'update']);
    Route::delete('/comments/{id}',         [CommentController::class, 'destroy']);
});

// ── Phase 2 / Forum: реакции ──
// Палитра доступна гостям (для рендера ленты).
// Toggle — auth + throttle:30,1 (кликают часто, но всё равно лимит).
Route::get('/reactions/palette', [ReactionController::class, 'palette']);
Route::get('/reactions/summary', [ReactionController::class, 'summary']);
Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    Route::post('/reactions/toggle', [ReactionController::class, 'toggle']);
});

// ── Phase 2 / Forum: публичные профили ──
// /u/:username — публичный URL, привязан к username (а не id).
// Возвращает 404 если username не задан, не существует или забанен.
Route::get('/users/{username}/profile', [UserProfileController::class, 'show']);
Route::get('/users/{username}/posts',   [UserProfileController::class, 'posts']);

// ── Phase 3 / Forum: подписки ──
// Список подписчиков и подписок — публично (можно посмотреть кто на кого).
// Сами действия — auth + throttle:30,1.
Route::get('/users/{username}/followers', [FollowController::class, 'followers']);
Route::get('/users/{username}/following', [FollowController::class, 'following']);
Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    Route::post('/users/{username}/follow',   [FollowController::class, 'follow']);
    Route::delete('/users/{username}/follow', [FollowController::class, 'unfollow']);
});

// Отзывы для игры
Route::get('/games/{gameId}/reviews', [ReviewController::class, 'index']);
// throttle:3,5 — 3 отзыва за 5 минут с одного юзера. Анти-spam-отзывы.
Route::middleware(['auth:sanctum', 'throttle:3,5'])->post('/games/{gameId}/reviews', [ReviewController::class, 'store']);

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
