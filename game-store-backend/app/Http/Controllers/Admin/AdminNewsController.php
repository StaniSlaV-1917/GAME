<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminNewsController extends Controller
{
    public function index()
    {
        return News::orderByDesc('published_at')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|max:2048',
            'published_at' => 'nullable|date',
        ]);

        if ($request->hasFile('image')) {
            // Сохраняем файл и получаем относительный путь
            $path = $request->file('image')->store('public/images/news');
            // Сохраняем в базу только путь относительно папки 'storage/app'
            $validated['image'] = str_replace('public/', '', $path);
        }

        $news = News::create($validated);

        return response()->json($news, 201);
    }

    public function show(News $news)
    {
        return $news;
    }

    public function update(Request $request, News $news)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|sometimes|image|max:2048',
            'published_at' => 'nullable|date',
        ]);

        if ($request->hasFile('image')) {
            // Если есть старое изображение, удаляем его
            if ($news->image) {
                Storage::disk('public')->delete($news->image);
            }

            // Сохраняем новый файл и получаем относительный путь
            $path = $request->file('image')->store('public/images/news');
            // Сохраняем в базу только путь относительно папки 'storage/app/public'
            $validated['image'] = str_replace('public/', '', $path);
        } 

        $news->update($validated);

        // Загружаем модель заново, чтобы получить аксесуар
        $news->refresh();

        return response()->json($news);
    }

    public function destroy(News $news)
    {
        if ($news->image) {
            Storage::disk('public')->delete($news->image);
        }

        $news->delete();

        return response()->json(null, 204);
    }
}
