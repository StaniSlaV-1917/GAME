<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Подписки между пользователями.
 * Phase 3 / Batch A.
 *
 * Семантика:
 *   POST   /api/users/{username}/follow      — подписаться
 *   DELETE /api/users/{username}/follow      — отписаться
 *   GET    /api/users/{username}/followers   — список подписчиков
 *   GET    /api/users/{username}/following   — список подписок
 */
class FollowController extends Controller
{
    /**
     * POST /api/users/{username}/follow
     * Подписаться на пользователя.
     */
    public function follow(Request $request, string $username)
    {
        $user = $request->user();
        if ($user->isFrozen()) {
            abort(403, 'Аккаунт заморожен. Подписки недоступны.');
        }

        $target = $this->findByUsername($username);

        if ($target->id === $user->id) {
            throw ValidationException::withMessages([
                'username' => ['Нельзя подписаться на самого себя.'],
            ]);
        }

        // Идемпотентно: если уже подписан — возвращаем текущее состояние
        $existing = Follow::where('follower_id', $user->id)
            ->where('followed_id', $target->id)
            ->first();

        if ($existing) {
            return response()->json([
                'following' => true,
                'followers_count' => $target->followers_count,
                'following_count' => $user->following_count,
                'message' => 'Уже подписаны.',
            ]);
        }

        DB::transaction(function () use ($user, $target) {
            Follow::create([
                'follower_id' => $user->id,
                'followed_id' => $target->id,
            ]);
            $user->increment('following_count');
            $target->increment('followers_count');
        });

        $user->refresh();
        $target->refresh();

        return response()->json([
            'following' => true,
            'followers_count' => $target->followers_count,
            'following_count' => $user->following_count,
            'message' => 'Подписка оформлена.',
        ], 201);
    }

    /**
     * DELETE /api/users/{username}/follow
     * Отписаться.
     */
    public function unfollow(Request $request, string $username)
    {
        $user = $request->user();
        $target = $this->findByUsername($username);

        $existing = Follow::where('follower_id', $user->id)
            ->where('followed_id', $target->id)
            ->first();

        if (!$existing) {
            return response()->json([
                'following' => false,
                'followers_count' => $target->followers_count,
                'following_count' => $user->following_count,
                'message' => 'Подписки не было.',
            ]);
        }

        DB::transaction(function () use ($user, $target, $existing) {
            $existing->delete();
            // decrement через max(0, -1) — защита от ухода в отрицание
            // если счётчик случайно сбился
            if ($user->following_count > 0) $user->decrement('following_count');
            if ($target->followers_count > 0) $target->decrement('followers_count');
        });

        $user->refresh();
        $target->refresh();

        return response()->json([
            'following' => false,
            'followers_count' => $target->followers_count,
            'following_count' => $user->following_count,
            'message' => 'Подписка отменена.',
        ]);
    }

    /**
     * GET /api/users/{username}/followers
     * Список подписчиков (плюс is_following_me для текущего юзера).
     */
    public function followers(Request $request, string $username)
    {
        $target = $this->findByUsername($username);

        $followers = $target->followers()
            ->select('users.id', 'users.fullname', 'users.username', 'users.avatar', 'users.role')
            ->orderByDesc('follows.created_at')
            ->limit(100)
            ->get();

        return response()->json(['data' => $followers, 'total' => $target->followers_count]);
    }

    /**
     * GET /api/users/{username}/following
     */
    public function following(Request $request, string $username)
    {
        $target = $this->findByUsername($username);

        $following = $target->following()
            ->select('users.id', 'users.fullname', 'users.username', 'users.avatar', 'users.role')
            ->orderByDesc('follows.created_at')
            ->limit(100)
            ->get();

        return response()->json(['data' => $following, 'total' => $target->following_count]);
    }

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
