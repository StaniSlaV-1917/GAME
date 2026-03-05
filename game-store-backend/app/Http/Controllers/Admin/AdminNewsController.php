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
            'image' => 'nullable|image|max:2048', // Теперь это файл изображения
            'published_at' => 'nullable|date',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('public/images/news');
            $validated['image'] = Storage::url($path);
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
            'image' => 'nullable|image|max:2048',
            'published_at' => 'nullable|date',
        ]);

        if ($request->hasFile('image')) {
            // Удаляем старое изображение, если оно есть
            if ($news->image) {
                Storage::delete(str_replace('/storage', 'public', $news->image));
            }

            $path = $request->file('image')->store('public/images/news');
            $validated['image'] = Storage::url($path);
        }

        $news->update($validated);

        return response()->json($news);
    }

    public function destroy(News $news)
    {
        if ($news->image) {
            Storage::delete(str_replace('/storage', 'public', $news->image));
        }

        $news->delete();

        return response()->json(null, 204);
    }
}
