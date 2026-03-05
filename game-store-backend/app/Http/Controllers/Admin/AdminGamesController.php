<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;

class AdminGamesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Просто возвращаем все игры, без пагинации, для простоты админки
        return Game::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'developer' => 'required|string|max:255',
            'publisher' => 'required|string|max:255',
            'release_date' => 'required|date',
            'genre' => 'required|string|max:100',
            'image_url' => 'nullable|url',
            'poster_url' => 'nullable|url',
            'is_hit' => 'boolean',
        ]);

        $game = Game::create($validated);
        return response()->json($game, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Game $game)
    {
        return $game;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Game $game)
    {
        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'string',
            'price' => 'numeric|min:0',
            'developer' => 'string|max:255',
            'publisher' => 'string|max:255',
            'release_date' => 'date',
            'genre' => 'string|max:100',
            'image_url' => 'nullable|url',
            'poster_url' => 'nullable|url',
            'is_hit' => 'boolean',
        ]);

        $game->update($validated);
        return $game;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Game $game)
    {
        $game->delete();
        return response()->json(null, 204); // No Content
    }
}
