<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;

class AdminGameController extends Controller
{
    // список игр (можно потом добавить пагинацию/фильтры)
    public function index()
    {
        $games = Game::orderByDesc('id')->get();

        return response()->json($games);
    }

public function store(Request $request)
{
    $data = $request->validate([
        'title'            => 'required|string|max:255',
        'genre'            => 'nullable|string|max:255',
        'platform'         => 'nullable|string|max:255',
        'price'            => 'required|numeric|min:0',
        'rating'           => 'nullable|numeric|min:0|max:10',
        'description'      => 'nullable|string',
        'stopgame_url_code'=> 'nullable|string|max:255',
        'is_featured'      => 'boolean',
        'is_new'           => 'boolean',
        'old_price'        => 'nullable|numeric|min:0',
        'discount_percent' => 'nullable|integer|min:0|max:100',
        'release_year'     => 'nullable|integer|min:1980',
        // путь, если выбираешь ручной / уже существующий
        'image'            => 'nullable|string|max:255',
        // файл обложки
        'image_file'       => 'nullable|image|max:2048', // до ~2MB
    ]);

    // если пришёл файл — сохраняем его в public/img
    if ($request->hasFile('image_file')) {
        $file = $request->file('image_file');

        $filename = time() . '_' . $file->getClientOriginalName();
        // public/img
        $file->move(public_path('img'), $filename);

        // в БД будем хранить СТРОКУ без слеша в начале: "img/xxx.png"
        $data['image'] = 'img/' . $filename;
    }

    $game = Game::create($data);

    return response()->json([
        'message' => 'Игра создана',
        'game'    => $game,
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
        'rating'           => 'nullable|numeric|min:0|max:10',
        'description'      => 'nullable|string',
        'stopgame_url_code'=> 'nullable|string|max:255',
        'is_featured'      => 'boolean',
        'is_new'           => 'boolean',
        'old_price'        => 'nullable|numeric|min:0',
        'discount_percent' => 'nullable|integer|min:0|max:100',
        'release_year'     => 'nullable|integer|min:1980',
        'image'            => 'nullable|string|max:255',
        'image_file'       => 'nullable|image|max:2048',
    ]);

    if ($request->hasFile('image_file')) {
        $file = $request->file('image_file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('img'), $filename);
        $data['image'] = 'img/' . $filename;
    }

    $game->update($data);

    return response()->json([
        'message' => 'Игра обновлена',
        'game'    => $game->fresh(),
    ]);
}


    public function destroy(int $id)
    {
        $game = Game::findOrFail($id);
        $game->delete();

        return response()->json([
            'message' => 'Игра удалена',
        ]);
    }
}
