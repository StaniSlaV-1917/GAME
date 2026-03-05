<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

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
            'image' => 'nullable|string|max:255',
            'published_at' => 'nullable|date',
        ]);

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
            'image' => 'nullable|string|max:255',
            'published_at' => 'nullable|date',
        ]);

        $news->update($validated);

        return response()->json($news);
    }

    public function destroy(News $news)
    {
        $news->delete();

        return response()->json(null, 204);
    }
}
