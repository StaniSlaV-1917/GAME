'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminGameController extends Controller
{
    public function index()
    {
        // Загружаем игры вместе с их галереями
        $games = Game::with('images')->orderByDesc('id')->get();
        return response()->json($games);
    }

    // Метод для получения ОДНОЙ игры со связанными данными
    public function show($id)
    {
        $game = Game::with('images')->findOrFail($id);
        return response()->json($game);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'genre'            => 'nullable|string|max:255',
            'platform'         => 'nullable|string|max:255',
            'price'            => 'required|numeric|min:0',
            'description'      => 'nullable|string',
            'trailer_url'      => 'nullable|string|max:255', // Валидация для трейлера
            'stopgame_url_code'=> 'nullable|string|max:255',
            'is_featured'      => 'boolean',
            'is_new'           => 'boolean',
            'old_price'        => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'release_year'     => 'nullable|integer|min:1980',
            'image'            => 'nullable|string|max:255',
            'image_file'       => 'nullable|image|max:2048',
            // Валидация для файлов галереи
            'gallery_files'    => 'nullable|array',
            'gallery_files.*'  => 'image|max:2048' // Каждый файл - картинка до 2MB
        ]);

        // Оборачиваем создание в транзакцию для надежности
        $game = DB::transaction(function () use ($request, $data) {
            // 1. Сохраняем основное изображение
            if ($request->hasFile('image_file')) {
                $file = $request->file('image_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('img'), $filename);
                $data['image'] = 'img/' . $filename;
            }

            // 2. Создаем игру
            $game = Game::create($data);

            // 3. Сохраняем галерею
            if ($request->hasFile('gallery_files')) {
                foreach ($request->file('gallery_files') as $file) {
                    $filename = time() . '_' . $file->getClientOriginalName();
                    // Сохраняем в отдельную папку
                    $file->move(public_path('img/gallery'), $filename);
                    $game->images()->create([
                        'path' => 'img/gallery/' . $filename
                    ]);
                }
            }
            return $game;
        });

        return response()->json([
            'message' => 'Игра создана',
            // Возвращаем игру с загруженной галереей
            'game'    => $game->load('images'),
        ], 201);
    }


    public function update(Request $request, $id)
    {
        $game = Game::findOrFail($id);

        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'genre'            => 'nullable|string|max:255',
            'platform'         => 'nullable|string|max:255',
            'price'            => 'required|numeric|min:0',
            'description'      => 'nullable|string',
            'trailer_url'      => 'nullable|string|max:255', // Валидация для трейлера
            'stopgame_url_code'=> 'nullable|string|max:255',
            'is_featured'      => 'boolean',
            'is_new'           => 'boolean',
            'old_price'        => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'release_year'     => 'nullable|integer|min:1980',
            'image'            => 'nullable|string|max:255',
            'image_file'       => 'nullable|image|max:2048',
            'gallery_files'    => 'nullable|array',
            'gallery_files.*'  => 'image|max:2048'
        ]);

        DB::transaction(function () use ($request, $game, $data) {
            // 1. Обновление основного изображения (если пришел новый файл)
            if ($request->hasFile('image_file')) {
                // Опционально: удаляем старый файл, если он был
                if ($game->image && file_exists(public_path($game->image))) {
                    unlink(public_path($game->image));
                }
                $file = $request->file('image_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('img'), $filename);
                $data['image'] = 'img/' . $filename;
            }

            // 2. Обновляем основные данные игры
            $game->update($data);

            // 3. Добавляем новые изображения в галерею
            if ($request->hasFile('gallery_files')) {
                foreach ($request->file('gallery_files') as $file) {
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $file->move(public_path('img/gallery'), $filename);
                    $game->images()->create([
                        'path' => 'img/gallery/' . $filename
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Игра обновлена',
            'game'    => $game->fresh()->load('images'),
        ]);
    }

    public function destroy(int $id)
    {
        $game = Game::with('images')->findOrFail($id);

        // Удаляем все файлы галереи и основное изображение
        foreach ($game->images as $image) {
            if (file_exists(public_path($image->path))) {
                unlink(public_path($image->path));
            }
        }
        if ($game->image && file_exists(public_path($game->image))) {
            unlink(public_path($game->image));
        }

        $game->delete(); // Благодаря cascadeOnDelete в миграции, связанные картинки из БД удалятся

        return response()->json(['message' => 'Игра удалена']);
    }

    // Новый метод для удаления ОДНОГО изображения из галереи
    public function destroyImage(int $imageId)
    {
        $image = GameImage::findOrFail($imageId);

        // Удаляем файл с диска
        if (file_exists(public_path($image->path))) {
            unlink(public_path($image->path));
        }

        // Удаляем запись из БД
        $image->delete();

        return response()->json(['message' => 'Изображение удалено']);
    }
}
''