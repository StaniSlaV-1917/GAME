<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Отдаем все новости, отсортированные по дате публикации (сначала новые)
        // Выбираем только нужные для списка поля
        return News::select('id', 'title', 'image', 'published_at')
            ->whereNotNull('published_at')
            ->where('published_at', '<', now())
            ->orderByDesc('published_at')
            ->paginate(10); // Добавляем пагинацию для производительности
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Находим новость по ID. Если не найдена - будет ошибка 404
        $news = News::findOrFail($id);
        return response()->json($news);
    }
}
