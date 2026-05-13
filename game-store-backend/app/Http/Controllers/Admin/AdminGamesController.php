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
        return Game::with('images')
            ->withCount([
                'keys as total_keys_count',
                'keys as available_keys_count' => fn ($q) => $q->where('is_issued', false),
            ])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function show(Game $game)
    {
        $game->load('images');
        $game->loadCount([
            'keys as total_keys_count',
            'keys as available_keys_count' => fn ($q) => $q->where('is_issued', false),
        ]);
        return $game;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'                  => 'required|string|max:255',
            'genre'                  => 'nullable|string|max:100',
            'platform'               => 'nullable|string|max:100',
            'price'                  => 'required|numeric|min:0',
            'rating'                 => 'nullable|numeric|min:0|max:10',
            'description'            => 'nullable|string',
            'os_requirements'        => 'nullable|string|max:255',
            'processor_requirements' => 'nullable|string|max:255',
            'ram_requirements'       => 'nullable|string|max:255',
            'graphics_requirements'  => 'nullable|string|max:255',
            'storage_requirements'   => 'nullable|string|max:255',
            'image'                  => 'nullable|string',
            'trailer_url'            => 'nullable|url|max:255',
            'stopgame_url_code'      => 'nullable|string|max:255',
            'is_featured'            => 'boolean',
            'is_new'                 => 'boolean',
            'old_price'              => 'nullable|numeric|min:0',
            'discount_percent'       => 'nullable|integer|min:0|max:100',
            'release_year'           => 'nullable|integer|min:1980',
        ]);

        $game = Game::create($validated);
        $game->load('images');
        $game->loadCount([
            'keys as total_keys_count',
            'keys as available_keys_count' => fn ($q) => $q->where('is_issued', false),
        ]);

        return response()->json($game, 201);
    }

    public function update(Request $request, Game $game)
    {
        Log::info('ADMIN UPDATE GAME REQUEST', [
            'game_id' => $game->id,
            'all'     => $request->all(),
        ]);

        $validated = $request->validate([
            'title'                  => 'sometimes|string|max:255',
            'genre'                  => 'sometimes|nullable|string|max:100',
            'platform'               => 'sometimes|nullable|string|max:100',
            'price'                  => 'sometimes|numeric|min:0',
            'rating'                 => 'sometimes|nullable|numeric|min:0|max:10',
            'description'            => 'sometimes|nullable|string',
            'os_requirements'        => 'sometimes|nullable|string|max:255',
            'processor_requirements' => 'sometimes|nullable|string|max:255',
            'ram_requirements'       => 'sometimes|nullable|string|max:255',
            'graphics_requirements'  => 'sometimes|nullable|string|max:255',
            'storage_requirements'   => 'sometimes|nullable|string|max:255',
            'image'                  => 'sometimes|nullable|string',
            'trailer_url'            => 'sometimes|nullable|url|max:255',
            'stopgame_url_code'      => 'sometimes|nullable|string|max:255',
            'is_featured'            => 'sometimes|boolean',
            'is_new'                 => 'sometimes|boolean',
            'old_price'              => 'sometimes|nullable|numeric|min:0',
            'discount_percent'       => 'sometimes|nullable|integer|min:0|max:100',
            'release_year'           => 'sometimes|nullable|integer|min:1980',
        ]);

        Log::info('ADMIN UPDATE GAME VALIDATED', $validated);

        $game->update($validated);

        Log::info('ADMIN UPDATE GAME AFTER UPDATE', $game->fresh()->toArray());

        $game->load('images');
        $game->loadCount([
            'keys as total_keys_count',
            'keys as available_keys_count' => fn ($q) => $q->where('is_issued', false),
        ]);

        return response()->json($game);
    }

    public function destroy(Game $game)
    {
        DB::transaction(function () use ($game) {
            foreach ($game->images as $image) {
                $this->deleteImageFile($image->path);
                $image->delete();
            }
            $game->delete();
        });

        return response()->json(null, 204);
    }

    /**
     * POST /admin/games/{game}/gallery
     * Загружает несколько изображений в галерею игры.
     */
    public function uploadGallery(Request $request, Game $game)
    {
        $request->validate([
            'gallery'   => 'required|array',
            'gallery.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        foreach ($request->file('gallery') as $imageFile) {
            // Явно используем диск 'public', чтобы файл попал в storage/app/public/
            // и был доступен через /storage/ после php artisan storage:link
            $path = $imageFile->store('gallery/game_' . $game->id, 'public');
            $url  = Storage::disk('public')->url($path); // → /storage/gallery/game_X/file.jpg

            $game->images()->create([
                'path'    => $url,
                'game_id' => $game->id,
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

        $this->deleteImageFile($image->path);
        $image->delete();

        return response()->json(null, 204);
    }

    /**
     * POST /admin/games/{game}/cover
     * Загружает обложку для игры и возвращает URL.
     * Вызывается отдельно от сохранения данных игры (фронт получает URL
     * и затем подставляет его в поле image при PUT/POST игры).
     */
    public function uploadCover(Request $request, Game $game)
    {
        $request->validate([
            'cover' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        // Удаляем старую обложку, если она была загружена через нас (путь /storage/...)
        if ($game->image && str_starts_with($game->image, '/storage/covers/')) {
            $this->deleteImageFile($game->image);
        }

        $path = $request->file('cover')->store('covers', 'public');
        $url  = Storage::disk('public')->url($path); // → /storage/covers/filename.jpg

        $game->update(['image' => $url]);

        return response()->json(['url' => $url]);
    }

    /**
     * Удаляет физический файл, зная его публичный URL вида /storage/...
     */
    private function deleteImageFile(string $publicUrl): void
    {
        // /storage/gallery/game_1/file.jpg → gallery/game_1/file.jpg (на диске public)
        $relativePath = ltrim(str_replace('/storage', '', parse_url($publicUrl, PHP_URL_PATH) ?? $publicUrl), '/');
        if ($relativePath) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
