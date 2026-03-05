<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameImage;
use Illuminate\Http\Request;

class GameImageController extends Controller
{
    // Список картинок для игры (если нужно отдельно)
    public function index($gameId)
    {
        $game = Game::with('images')->findOrFail($gameId);

        return response()->json($game->images);
    }

    // Добавить картинку к игре
    public function store(Request $request, $gameId)
    {
        $game = Game::findOrFail($gameId);

        $data = $request->validate([
            'path'       => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['game_id'] = $game->id;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $image = GameImage::create($data);

        return response()->json([
            'message' => 'Картинка добавлена',
            'image'   => $image,
        ], 201);
    }

    // Обновить sort_order / path
    public function update(Request $request, $gameId, $imageId)
    {
        $game = Game::findOrFail($gameId);
        $image = GameImage::where('game_id', $game->id)->findOrFail($imageId);

        $data = $request->validate([
            'path'       => 'sometimes|required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $image->fill($data);
        $image->save();

        return response()->json([
            'message' => 'Картинка обновлена',
            'image'   => $image,
        ]);
    }

    // Удалить картинку
    public function destroy($gameId, $imageId)
    {
        $game = Game::findOrFail($gameId);
        $image = GameImage::where('game_id', $game->id)->findOrFail($imageId);
        $image->delete();

        return response()->json([
            'message' => 'Картинка удалена',
        ]);
    }
}
