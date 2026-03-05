<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    // GET /api/admin/reviews
    // Список отзывов, можно фильтровать по game_id и status
    public function index(Request $request)
{
    $perPage = (int) $request->get('per_page', 30);

    $query = Review::with(['game', 'user'])
        ->orderByDesc('created_at');

    if ($request->filled('game_id')) {
        $query->where('game_id', (int)$request->get('game_id'));
    }

    if ($request->filled('user_id')) {
        $query->where('user_id', (int)$request->get('user_id'));
    }

    if ($request->filled('status')) {
        $query->where('status', $request->get('status'));
    }

    if ($request->filled('search')) {
        $search = $request->get('search');
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('body', 'like', "%{$search}%");
        });
    }

    $reviews = $query->paginate($perPage);

    return response()->json($reviews);
}

    // PUT /api/admin/reviews/{id}
    // Обновление статуса и текста отзыва
    public function update(Request $request, $id)
    {
        $review = Review::withTrashed()->findOrFail($id);

        $data = $request->validate([
            'status' => 'nullable|string|in:pending,approved,frozen,rejected',
            'title'  => 'nullable|string|max:150',
            'body'   => 'nullable|string',
        ]);

        if (array_key_exists('status', $data)) {
            $review->status = $data['status'];
        }
        if (array_key_exists('title', $data)) {
            $review->title = $data['title'];
        }
        if (array_key_exists('body', $data)) {
            $review->body = $data['body'];
        }

        $review->save();

        return response()->json($review->load(['game', 'user']));
    }

    // DELETE /api/admin/reviews/{id}
    // Soft delete отзыва
    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $review->delete(); // soft delete (нужен SoftDeletes в модели Review)

        return response()->json(['message' => 'Отзыв удалён']);
    }

    // POST /api/admin/reviews/{id}/restore
    // Восстановление soft‑deleted отзыва
    public function restore($id)
    {
        $review = Review::onlyTrashed()->findOrFail($id);
        $review->restore();

        return response()->json([
            'message' => 'Отзыв восстановлен',
            'review' => $review->load(['game', 'user']),
        ]);
    }
}
