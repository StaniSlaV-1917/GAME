<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    // GET /api/games
    public function index(Request $request)
    {
        // список игр с картинками (отзывы здесь не нужны)
        $games = Game::with('images')
            ->orderByDesc('id')
            ->get();

        return response()->json($games);
    }

    // GET /api/games/{id}
    public function show($id)
    {
        // подгружаем:
        //  - images (галерея)
        //  - reviews, но только:
        //      * не soft‑deleted
        //      * со статусом approved (выводим только одобренные адмном)
        //  - user у отзыва
        $game = Game::with([
            'images',
            'reviews' => function ($query) {
                $query
                    ->where('status', 'approved')   // показываем только одобренные
                    ->orderByDesc('created_at');    // последние сверху
            },
            'reviews.user',
        ])->findOrFail($id);

        return response()->json($game);
    }
}
