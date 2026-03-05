<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request, $gameId)
    {
        $user = Auth::user();

        $game = Game::findOrFail($gameId);

        // один отзыв на игру от одного пользователя
        $existing = Review::where('game_id', $game->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Вы уже оставляли отзыв для этой игры.',
            ], 422);
        }

        $data = $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'title'  => 'nullable|string|max:150',
            'body'   => 'nullable|string',
        ]);

        $review = Review::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'rating'  => $data['rating'],
            'title'   => $data['title'] ?? null,
            'body'    => $data['body'] ?? null,
        ]);

        // пересчёт среднего рейтинга и количества отзывов
        $game->loadCount('reviews');
        $avg = Review::where('game_id', $game->id)->avg('rating');

        $game->rating = $avg;          // либо average_review_rating, если есть такое поле
        $game->reviews_count = $game->reviews_count; // если есть столбец — обнови его
        $game->save();

        // подгружаем автора для фронта
        $review->load('user');

        return response()->json([
            'message' => 'Отзыв добавлен',
            'review'  => $review,
            'game'    => $game->fresh(['reviews.user']),
        ], 201);
    }
}
