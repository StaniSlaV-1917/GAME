<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

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
            // Сортировка по умолчанию
            $query->orderByDesc('id');
        }

        $games = $query->with('images')->get();

        return response()->json($games);
    }

    // GET /api/genres - Новый метод для получения списка жанров
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
        ])->findOrFail($id);

        return response()->json($game);
    }

    // GET /api/games/{gameId}/mods
    public function getMods($gameId)
    {
        $game = Game::findOrFail($gameId);
        $mods = $game->mods()->orderBy('sort_order')->orderBy('popularity_score', 'desc')->get();

        return response()->json($mods);
    }
}
