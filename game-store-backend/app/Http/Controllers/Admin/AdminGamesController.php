<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminGamesController extends Controller
{
    public function index()
    {
        // Загружаем игры вместе с их галереями
        return Game::with('images')->orderBy('id', 'desc')->get();
    }

    public function show(Game $game)
    {
        // Загружаем конкретную игру с ее галереей
        $game->load('images');
        return $game;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'genre' => 'string|max:100',
            'platform' => 'string|max:100',
            'price' => 'required|numeric|min:0',
            'rating' => 'nullable|numeric|min:0|max:10',
            'description' => 'nullable|string',
            'image' => 'nullable|string', // Теперь это основное изображение
            'trailer_url' => 'nullable|string|max:255',
            'stopgame_url_code' => 'nullable|string|max:255',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'old_price' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'release_year' => 'nullable|integer|min:1980',
            'gallery' => 'nullable|array', // Принимаем массив файлов
            'gallery.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048' // Валидация для каждого файла
        ]);

        $game = Game::create($validated);

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $imageFile) {
                $path = $imageFile->store('public/gallery/game_' . $game->id);
                $game->images()->create([
                    'path' => Storage::url($path),
                    'game_id' => $game->id
                ]);
            }
        }

        $game->load('images'); // Загружаем свежесозданную галерею
        return response()->json($game, 201);
    }

    public function update(Request $request, Game $game)
    {
        $validated = $request->validate([
            'title' => 'string|max:255',
            'genre' => 'string|max:100',
            'platform' => 'string|max:100',
            'price' => 'numeric|min:0',
            'rating' => 'nullable|numeric|min:0|max:10',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'trailer_url' => 'nullable|string|max:255',
            'stopgame_url_code' => 'nullable|string|max:255',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'old_price' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'release_year' => 'nullable|integer|min:1980',
        ]);

        $game->update($validated);
        $game->load('images'); // Возвращаем игру с уже существующей галереей

        return response()->json($game);
    }

     public function destroy(Game $game)
    {
        // Используем транзакцию для безопасного удаления
        DB::transaction(function () use ($game) {
            // Удаляем связанные изображения из хранилища и записи из БД
            foreach ($game->images as $image) {
                // Преобразуем URL в относительный путь для Storage
                $path = str_replace('/storage', 'public', $image->path);
                Storage::delete($path);
                $image->delete();
            }
            // Удаляем саму игру
            $game->delete();
        });

        return response()->json(null, 204);
    }

    // Метод для добавления изображений в галерею существующей игры
    public function uploadGallery(Request $request, Game $game)
    {
        $request->validate([
            'gallery' => 'required|array',
            'gallery.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        foreach ($request->file('gallery') as $imageFile) {
            $path = $imageFile->store('public/gallery/game_' . $game->id);
            $game->images()->create([
                'path' => Storage::url($path),
                'game_id' => $game->id
            ]);
        }

        $game->load('images');
        return response()->json($game->images);
    }

    // Метод для удаления изображения из галереи
    public function deleteGalleryImage(Game $game, GameImage $image)
    {
        // Убедимся, что изображение принадлежит указанной игре
        if ($image->game_id !== $game->id) {
            return response()->json(['message' => 'Image does not belong to this game.'], 403);
        }

        $path = str_replace('/storage', 'public', $image->path);
        Storage::delete($path);
        $image->delete();

        return response()->json(null, 204);
    }
}
