<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameKey;
use Illuminate\Http\Request;

class GameController extends Controller
{
    // GET /api/games
    public function index(Request $request)
    {
        $query = Game::query();

        // Фильтрация по жанру
        if ($request->has('genre') && $request->genre !== 'all') {
            $query->where('genre', $request->genre);
        }

        // Сортировка
        if ($request->has('sortBy')) {
            $sortBy = $request->sortBy;
            if ($sortBy === 'price_asc') {
                $query->orderBy('price', 'asc');
            } elseif ($sortBy === 'price_desc') {
                $query->orderBy('price', 'desc');
            } elseif ($sortBy === 'title_asc') {
                $query->orderBy('title', 'asc');
            } elseif ($sortBy === 'title_desc') {
                $query->orderBy('title', 'desc');
            } elseif ($sortBy === 'release_date_desc') {
                $query->orderBy('release_year', 'desc');
            } elseif ($sortBy === 'rating_desc') {
                $query->orderBy('average_review_rating', 'desc');
            }
        } else {
            $query->orderByDesc('id');
        }

        $games = $query
            ->with('images')
            ->withCount([
                'keys as total_keys_count',
                'keys as available_keys_count' => fn ($q) => $q->where('is_issued', false),
            ])
            ->get()
            ->map(fn ($g) => $this->appendInStock($g));

        return response()->json($games);
    }

    // GET /api/genres
    public function getGenres()
    {
        $genres = Game::select('genre')->distinct()->whereNotNull('genre')->orderBy('genre')->pluck('genre');
        return response()->json($genres);
    }

    // GET /api/games/{id}
    public function show($id)
    {
        $game = Game::with([
            'images',
            'reviews' => function ($query) {
                $query->where('status', 'approved')->orderByDesc('created_at');
            },
            'reviews.user',
        ])
        ->withCount([
            'keys as total_keys_count',
            'keys as available_keys_count' => fn ($q) => $q->where('is_issued', false),
        ])
        ->findOrFail($id);

        $forumPosts = \App\Models\Post::published()
            ->where('game_id', $game->id)
            ->withTag('#обзор')
            ->with('author:id,fullname,username,avatar,role')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get([
                'id', 'author_id', 'title', 'body', 'cover_url',
                'tags', 'reaction_count', 'comment_count', 'view_count',
                'published_at',
            ]);

        $gameArr = $this->appendInStock($game)->toArray();
        $gameArr['forum_reviews'] = $forumPosts;

        return response()->json($gameArr);
    }

    // GET /api/games/{gameId}/mods
    public function getMods($gameId)
    {
        $game = Game::findOrFail($gameId);
        $mods = $game->mods()->orderBy('sort_order')->orderBy('popularity_score', 'desc')->get();

        return response()->json($mods);
    }

    /**
     * Добавляет поле in_stock к объекту Game.
     *  – true  если у игры нет управляемых ключей (keys not tracked)
     *  – true  если есть хотя бы один свободный ключ
     *  – false если ключи управляются, но все выданы
     */
    private function appendInStock(Game $game): Game
    {
        $total     = (int) ($game->total_keys_count ?? 0);
        $available = (int) ($game->available_keys_count ?? 0);

        // total = 0 → ключи не управляются → в наличии
        $game->in_stock = ($total === 0) || ($available > 0);

        return $game;
    }
}
