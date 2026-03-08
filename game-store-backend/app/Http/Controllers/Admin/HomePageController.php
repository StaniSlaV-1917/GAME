<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

class HomePageController extends Controller
{
    /**
     * Получить данные для редактора главной страницы.
     * Пока что возвращаем только последние 5 новостей.
     */
    public function getEditorData()
    {
        // Берем 5 последних новостей, отсортированных по дате создания
        $latestNews = News::with('author:id,name')
        ->orderByDesc('published_at') // или ->orderByDesc('id')
        ->take(5)
        ->get();

        // Трансформируем данные в нужный формат
        $transformedNews = $latestNews->map(function ($newsItem) {
            return [
                'id' => $newsItem->id,
                'title' => $newsItem->title,
                // Создаем краткий отрывок из основного контента
                'excerpt' => mb_substr(strip_tags($newsItem->content), 0, 100) . '...',
                // Если автор есть, берем его имя, иначе - "Неизвестен"
                'author' => $newsItem->author ? $newsItem->author->name : 'Неизвестен',
                // Форматируем дату
                'date' => $newsItem->created_at->format('d.m.Y'),
            ];
        });

        return response()->json([
            'news' => $transformedNews,
        ]);
    }
}
