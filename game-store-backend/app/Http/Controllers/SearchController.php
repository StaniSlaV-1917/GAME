<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Mod;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Универсальный поиск по всему контенту сайта.
 *
 * GET /api/search?q=<query>&types=games,users,posts,mods (опц.)
 *
 * Возвращает: { games:[], users:[], posts:[], mods:[] }
 * Каждая категория ограничена 5 результатами для отзывчивости dropdown.
 *
 * ILIKE для case-insensitive search в Postgres. На MySQL/SQLite
 * автоматически работает через collation.
 */
class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $types = $request->query('types');
        $allowed = $types
            ? array_filter(array_map('trim', explode(',', $types)))
            : ['games', 'users', 'posts', 'mods'];

        // Минимум 2 символа для поиска (защита от мусорных запросов)
        if (mb_strlen($q) < 2) {
            return response()->json([
                'games' => [],
                'users' => [],
                'posts' => [],
                'mods'  => [],
                'q'     => $q,
            ]);
        }

        $like = '%' . $this->escapeLike($q) . '%';
        $isPgsql = DB::connection()->getDriverName() === 'pgsql';
        $likeOp  = $isPgsql ? 'ilike' : 'like';

        $result = ['q' => $q];

        // ── Games ──
        if (in_array('games', $allowed, true)) {
            $result['games'] = Game::where(function ($w) use ($likeOp, $like) {
                    $w->where('title', $likeOp, $like)
                      ->orWhere('genre', $likeOp, $like);
                })
                ->limit(5)
                ->get(['id', 'title', 'genre', 'price', 'image']);
        } else {
            $result['games'] = [];
        }

        // ── Users (только активные с username) ──
        if (in_array('users', $allowed, true)) {
            $result['users'] = User::whereNull('banned_at')
                ->whereNotNull('username')
                ->where(function ($w) use ($likeOp, $like) {
                    $w->where('username', $likeOp, $like)
                      ->orWhere('fullname', $likeOp, $like);
                })
                ->limit(5)
                ->get(['id', 'fullname', 'username', 'avatar', 'role']);
        } else {
            $result['users'] = [];
        }

        // ── Posts (только опубликованные approved) ──
        if (in_array('posts', $allowed, true)) {
            $result['posts'] = Post::published()
                ->where(function ($w) use ($likeOp, $like) {
                    $w->where('title', $likeOp, $like)
                      ->orWhere('body', $likeOp, $like);
                })
                ->with('author:id,fullname,username,avatar,role')
                ->limit(5)
                ->get(['id', 'title', 'author_id', 'cover_url', 'published_at']);
        } else {
            $result['posts'] = [];
        }

        // ── Mods ──
        if (in_array('mods', $allowed, true)) {
            $result['mods'] = Mod::where(function ($w) use ($likeOp, $like) {
                    $w->where('title', $likeOp, $like)
                      ->orWhere('description', $likeOp, $like);
                })
                ->with('game:id,title')
                ->limit(5)
                ->get();
        } else {
            $result['mods'] = [];
        }

        return response()->json($result);
    }

    /**
     * Escape % and _ так чтобы юзер не мог инжектить LIKE wildcards.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
