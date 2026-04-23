<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Mod;
use Illuminate\Http\Request;

class AdminModsController extends Controller
{
    public function index(Game $game)
    {
        return $game->mods()->orderBy('sort_order')->orderBy('popularity_score', 'desc')->get();
    }

    public function show(Game $game, Mod $mod)
    {
        if ($mod->game_id !== $game->id) {
            return response()->json(['message' => 'Mod does not belong to this game.'], 403);
        }
        return $mod;
    }

    public function store(Request $request, Game $game)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'external_url' => 'required|url|max:500',
            'source_site' => 'nullable|string|max:100',
            'author' => 'nullable|string|max:100',
            'version' => 'nullable|string|max:50',
            'download_count' => 'nullable|integer|min:0',
            'popularity_score' => 'nullable|numeric|min:0|max:10',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $mod = $game->mods()->create($validated);

        return response()->json($mod, 201);
    }

    public function update(Request $request, Game $game, Mod $mod)
    {
        if ($mod->game_id !== $game->id) {
            return response()->json(['message' => 'Mod does not belong to this game.'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'external_url' => 'sometimes|url|max:500',
            'source_site' => 'sometimes|nullable|string|max:100',
            'author' => 'sometimes|nullable|string|max:100',
            'version' => 'sometimes|nullable|string|max:50',
            'download_count' => 'sometimes|nullable|integer|min:0',
            'popularity_score' => 'sometimes|nullable|numeric|min:0|max:10',
            'is_featured' => 'sometimes|boolean',
            'sort_order' => 'sometimes|nullable|integer|min:0',
        ]);

        $mod->update($validated);

        return response()->json($mod);
    }

    public function destroy(Game $game, Mod $mod)
    {
        if ($mod->game_id !== $game->id) {
            return response()->json(['message' => 'Mod does not belong to this game.'], 403);
        }

        $mod->delete();

        return response()->json(null, 204);
    }
}