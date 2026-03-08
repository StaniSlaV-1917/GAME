<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AdminGamesController extends Controller
{
    public function index()
    {
        return Game::with('images')->orderBy('id', 'desc')->get();
    }

    public function show(Game $game)
    {
        $game->load('images');
        return $game;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'genre'             => 'nullable|string|max:100',
            'platform'          => 'nullable|string|max:100',
            'price'             => 'required|numeric|min:0',
            'rating'            => 'nullable|numeric|min:0|max:10',
            'description'       => 'nullable|string',
            'image'             => 'nullable|string',
            'trailer_url'       => 'nullable|url|max:255',
            'stopgame_url_code' => 'nullable|string|max:255',
            'is_featured'       => 'boolean',
            'is_new'            => 'boolean',
            'old_price'         => 'nullable|numeric|min:0',
            'discount_percent'  => 'nullable|integer|min:0|max:100',
            'release_year'      => 'nullable|integer|min:1980',
        ]);

        $game = Game::create($validated);
        $game->load('images');

        return response()->json($game, 201);
    }

    public function update(Request $request, Game $game)
    {
        Log::info('ADMIN UPDATE GAME REQUEST', [
            'game_id' => $game->id,
            'all'     => $request->all(),
        ]);

        $validated = $request->validate([
            'title'             => 'sometimes|string|max:255',
            'genre'             => 'sometimes|nullable|string|max:100',
            'platform'          => 'sometimes|nullable|string|max:100',
            'price'             => 'sometimes|numeric|min:0',
            'rating'            => 'sometimes|nullable|numeric|min:0|max:10',
            'description'       => 'sometimes|nullable|string',
            'image'             => 'sometimes|nullable|string',
            'trailer_url'       => 'sometimes|nullable|url|max:255',
            'stopgame_url_code' => 'sometimes|nullable|string|max:255',
            'is_featured'       => 'sometimes|boolean',
            'is_new'            => 'sometimes|boolean',
            'old_price'         => 'sometimes|nullable|numeric|min:0',
            'discount_percent'  => 'sometimes|nullable|integer|min:0|max:100',
            'release_year'      => 'sometimes|nullable|integer|min:1980',
        ]);

        Log::info('ADMIN UPDATE GAME VALIDATED', $validated);

        $game->update($validated);

        Log::info('ADMIN UPDATE GAME AFTER UPDATE', $game->fresh()->toArray());

        $game->load('images');

        return response()->json($game);
    }

    public function destroy(Game $game)
    {
        DB::transaction(function () use ($game) {
            foreach ($game->images as $image) {
                $path = str_replace('/storage', 'public', $image->path);
                Storage::delete($path);
                $image->delete();
            }
            $game->delete();
        });

        return response()->json(null, 204);
    }

    public function uploadGallery(Request $request, Game $game)
    {
        $request->validate([
            'gallery'   => 'required|array',
            'gallery.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        foreach ($request->file('gallery') as $imageFile) {
            $path = $imageFile->store('public/gallery/game_' . $game->id);
            $game->images()->create([
                'path'    => Storage::url($path),
                'game_id' => $game->id
            ]);
        }

        $game->load('images');
        return response()->json($game->images);
    }

    public function deleteGalleryImage(Game $game, GameImage $image)
    {
        if ($image->game_id !== $game->id) {
            return response()->json(['message' => 'Image does not belong to this game.'], 403);
        }

        $path = str_replace('/storage', 'public', $image->path);
        Storage::delete($path);
        $image->delete();

        return response()->json(null, 204);
    }
}
