<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

/**
 * Публичный API для постов форума.
 *
 * Phase 2 / Batch A — базовые эндпоинты:
 *   GET /api/posts          — лента с фильтрами и пагинацией
 *   GET /api/posts/{id}     — один пост (с инкрементом view_count)
 *
 * CRUD (POST/PUT/DELETE) — Batch C.
 * Комментарии — Batch D.
 * Реакции — Batch E.
 */
class PostController extends Controller
{
    /**
     * Лента постов.
     *
     * Query параметры:
     *   - page         (int)    : пагинация (default 1)
     *   - per_page     (int)    : 1..50 (default 20)
     *   - tag          (string) : фильтр по тегу (#обзор, #гайд, etc.)
     *   - game_id      (int)    : фильтр по игре
     *   - author_id    (int)    : посты конкретного юзера
     *   - sort         (string) : 'latest' (default), 'popular', 'discussed'
     *
     * Возвращает только опубликованные approved-public посты.
     */
    public function index(Request $request)
    {
        $perPage = min(50, max(1, (int) $request->input('per_page', 20)));
        $sort    = $request->input('sort', 'latest');

        $query = Post::published()
            ->with([
                'author:id,fullname,username,avatar,role',
                'game:id,title,image',
            ])
            ->select([
                'id', 'author_id', 'author_role', 'title', 'body',
                'cover_url', 'game_id', 'tags', 'visibility',
                'is_featured', 'is_pinned',
                'reaction_count', 'comment_count', 'view_count',
                'published_at', 'created_at',
            ]);

        // ── Фильтры ──
        if ($tag = $request->input('tag')) {
            $query->withTag($tag);
        }
        if ($gameId = $request->input('game_id')) {
            $query->where('game_id', $gameId);
        }
        if ($authorId = $request->input('author_id')) {
            $query->where('author_id', $authorId);
        }

        // ── Сортировка ──
        match ($sort) {
            'popular'   => $query->orderByDesc('reaction_count')->orderByDesc('published_at'),
            'discussed' => $query->orderByDesc('comment_count')->orderByDesc('published_at'),
            default     => $query->feed(), // pinned > published_at desc
        };

        $posts = $query->paginate($perPage);

        return response()->json([
            'data'         => $posts->items(),
            'current_page' => $posts->currentPage(),
            'last_page'    => $posts->lastPage(),
            'per_page'     => $posts->perPage(),
            'total'        => $posts->total(),
        ]);
    }

    /**
     * Один пост по ID. Инкрементирует view_count атомарно.
     * Eager-load автор + игра + первые 50 корневых комментариев.
     */
    public function show(int $id)
    {
        $post = Post::published()
            ->with([
                'author:id,fullname,username,avatar,role',
                'game:id,title,image,price',
                'allowedReactions',
            ])
            ->findOrFail($id);

        // Атомарный инкремент view_count (без race condition)
        Post::where('id', $id)->increment('view_count');
        $post->view_count++;

        return response()->json($post);
    }
}
