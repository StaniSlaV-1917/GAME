<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Публичный API профилей пользователей.
 *
 * Phase 2 / Batch B — рут /u/:username и связанные эндпоинты.
 *
 * Доступ:
 *   • Любой (включая гостей) — посмотреть профиль и посты
 *   • Забаненные / удалённые — возвращаем 404 («не существует»)
 *
 * Username нормализуется на бэке: всегда lowercase. Поиск кейс-
 * нечувствительный через ILIKE на Postgres / LOWER на других СУБД.
 */
class UserProfileController extends Controller
{
    /**
     * GET /api/users/{username}/profile
     *
     * Публичный профиль с базовыми статами.
     */
    public function show(Request $request, string $username)
    {
        $user = $this->findByUsername($username);

        // Статистика: считаем за один проход вместо N запросов.
        $postsCount = Post::where('author_id', $user->id)
            ->where('moderation_status', 'approved')
            ->whereNotNull('published_at')
            ->count();

        $commentsCount = \DB::table('comments')
            ->where('author_id', $user->id)
            ->where('moderation_status', 'approved')
            ->whereNull('deleted_at')
            ->count();

        $reactionsGiven = Reaction::where('user_id', $user->id)->count();

        $reactionsReceived = Reaction::whereIn('reactable_type', ['App\\Models\\Post', 'App\\Models\\Comment'])
            ->whereExists(function ($q) use ($user) {
                $q->from('posts')
                  ->whereColumn('posts.id', 'reactions.reactable_id')
                  ->where('reactions.reactable_type', 'App\\Models\\Post')
                  ->where('posts.author_id', $user->id);
            })
            ->count();

        // Phase 3: подписан ли текущий юзер на этого.
        //
        // ВАЖНО: маршрут /api/users/{username}/profile публичный (без
        // auth:sanctum middleware) → $request->user() ВСЕГДА null даже
        // для залогиненного юзера с валидным Bearer-токеном (Sanctum
        // не резолвит токен без middleware).
        // Решение: явно резолвим через guard('sanctum')->user() —
        // работает независимо от middleware, возвращает null если токена нет.
        $viewer = Auth::guard('sanctum')->user();
        $isFollowedByMe = false;
        if ($viewer && $viewer->id !== $user->id) {
            $isFollowedByMe = Follow::where('follower_id', $viewer->id)
                ->where('following_id', $user->id)
                ->exists();
        }

        // Phase 3 / Batch C — библиотека игр (cross-ref shop ↔ forum).
        // Уникальный список игр из orders юзера. Видна по privacy-настройке.
        $isOwn = $viewer && $viewer->id === $user->id;
        $libraryVisible = $isOwn || ($user->library_public ?? true);

        $library = [];
        $libraryCount = 0;
        if ($libraryVisible) {
            $library = \DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('games', 'games.id', '=', 'order_items.game_id')
                ->where('orders.user_id', $user->id)
                ->select(
                    'games.id',
                    'games.title',
                    'games.image',
                    'games.platform',
                    \DB::raw('MIN(orders.order_date) as purchased_at')
                )
                ->groupBy('games.id', 'games.title', 'games.image', 'games.platform')
                ->orderByDesc('purchased_at')
                ->limit(20)
                ->get();
            $libraryCount = $library->count();
        }

        return response()->json([
            'id'         => $user->id,
            'username'   => $user->username,
            'fullname'   => $user->fullname,
            'avatar'     => $user->avatar,
            'role'       => $user->role,
            'reg_date'   => $user->reg_date,
            'is_frozen'  => $user->isFrozen(),
            'stats'      => [
                'posts'              => $postsCount,
                'comments'           => $commentsCount,
                'reactions_given'    => $reactionsGiven,
                'reactions_received' => $reactionsReceived,
                'followers'          => (int) $user->followers_count,
                'following'          => (int) $user->following_count,
                'library'            => $libraryCount,
            ],
            'is_followed_by_me' => $isFollowedByMe,
            'library_public'    => (bool) ($user->library_public ?? true),
            'library'           => $library,
        ]);
    }

    /**
     * GET /api/users/{username}/posts
     *
     * Посты данного юзера (опубликованные и approved).
     * Делегируем фильтр на PostController через query, чтобы не
     * дублировать логику пагинации/сортировки.
     */
    public function posts(string $username, Request $request)
    {
        $user = $this->findByUsername($username);

        // Перенаправляем на PostController logic, передав author_id
        $request->merge(['author_id' => $user->id]);
        return app(PostController::class)->index($request);
    }

    /**
     * Helper: найти юзера по username.
     * Возвращает 404 если не найден / забанен / удалён.
     */
    private function findByUsername(string $username): User
    {
        $username = mb_strtolower(trim($username));

        $user = User::whereRaw('LOWER(username) = ?', [$username])->first();

        if (!$user || $user->isBanned()) {
            abort(404, 'Пользователь не найден');
        }

        return $user;
    }
}
