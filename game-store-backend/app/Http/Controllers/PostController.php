<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
     * Eager-load автор + игра + reactions summary.
     */
    public function show(Request $request, int $id)
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

        // Реакции — сводка с reacted_by_me флагом для текущего юзера
        $userId = optional($request->user())->id;
        $post->reactions_summary = ReactionController::batchSummary(
            Post::class,
            [$id],
            $userId
        )[$id] ?? [];

        return response()->json($post);
    }

    /**
     * POST /api/posts — создание поста.
     * Auth + frozen-гейт + валидация + throttle (через middleware на route).
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $this->guardFrozen($user);

        $data = $this->validatePost($request);

        // Нормализация тегов: lowercase, без пробелов, лимит 5 шт.
        $tags = collect($data['tags'])
            ->map(fn ($t) => mb_strtolower(trim($t)))
            ->filter(fn ($t) => $t !== '' && mb_strlen($t) <= 30)
            ->unique()
            ->take(5)
            ->values()
            ->all();

        if (empty($tags)) {
            throw ValidationException::withMessages([
                'tags' => ['Укажите минимум один тег.'],
            ]);
        }

        $post = Post::create([
            'author_id'         => $user->id,
            'author_role'       => $user->role,           // снимок роли
            'title'             => $data['title'] ?? null,
            'body'              => $data['body'],
            'cover_url'         => $data['cover_url'] ?? null,
            'game_id'           => $data['game_id'] ?? null,
            'tags'              => $tags,
            'visibility'        => $data['visibility'] ?? 'public',
            // verified-юзеры (manager/admin) — без премодерации, остальные
            // в queue. Можно расширить через Spatie permissions в Phase 8.
            'moderation_status' => in_array($user->role, ['admin', 'manager'])
                                    ? 'approved' : 'pending',
            'published_at'      => $request->boolean('publish_now', true)
                                    ? now() : null,
        ]);

        Log::info('[Post] created', [
            'post_id'           => $post->id,
            'author_id'         => $user->id,
            'moderation_status' => $post->moderation_status,
        ]);

        return response()->json($post->load('author:id,fullname,username,avatar,role'), 201);
    }

    /**
     * PUT /api/posts/{id} — редактирование поста.
     * Только автор может править. Админ может через AdminPostController
     * (Phase 8). После правки moderation_status сбрасывается в pending
     * (если не verified-юзер).
     */
    public function update(Request $request, int $id)
    {
        $user = $request->user();
        $this->guardFrozen($user);

        $post = Post::findOrFail($id);

        if ($post->author_id !== $user->id) {
            abort(403, 'Только автор может редактировать пост.');
        }

        $data = $this->validatePost($request);

        $tags = collect($data['tags'] ?? $post->tags ?? [])
            ->map(fn ($t) => mb_strtolower(trim($t)))
            ->filter(fn ($t) => $t !== '' && mb_strlen($t) <= 30)
            ->unique()
            ->take(5)
            ->values()
            ->all();

        $post->fill([
            'title'      => $data['title']      ?? $post->title,
            'body'       => $data['body']       ?? $post->body,
            'cover_url'  => array_key_exists('cover_url', $data) ? $data['cover_url'] : $post->cover_url,
            'game_id'    => array_key_exists('game_id', $data)   ? $data['game_id']   : $post->game_id,
            'tags'       => $tags,
            'visibility' => $data['visibility'] ?? $post->visibility,
        ]);

        // Сбрасываем pending, если юзер не verified
        if (!in_array($user->role, ['admin', 'manager'])) {
            $post->moderation_status = 'pending';
        }

        $post->save();

        return response()->json($post->load('author:id,fullname,username,avatar,role'));
    }

    /**
     * DELETE /api/posts/{id} — soft-delete поста.
     * Только автор. Каскадно удаляет комменты (через FK ON DELETE CASCADE).
     */
    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $post = Post::findOrFail($id);

        if ($post->author_id !== $user->id && $user->role !== 'admin') {
            abort(403, 'Удалить может только автор или админ.');
        }

        $post->delete();

        Log::info('[Post] deleted', [
            'post_id'  => $id,
            'actor_id' => $user->id,
        ]);

        return response()->json(['message' => 'Пост удалён.']);
    }

    /**
     * POST /api/posts/upload-cover — загрузка обложки на R2.
     * Auth + frozen-гейт. Возвращает URL для использования в cover_url.
     */
    public function uploadCover(Request $request)
    {
        $user = $request->user();
        $this->guardFrozen($user);

        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // 5 МБ
        ]);

        $file = $request->file('image');
        $ext  = $file->getClientOriginalExtension();
        $name = 'post-covers/' . $user->id . '/' . Str::uuid() . '.' . $ext;

        // Используем 'public' disk явно (R2 в проде, local в деве).
        $path = Storage::disk('public')->putFileAs(
            dirname($name),
            $file,
            basename($name)
        );

        // ВАЖНО: для R2-disk с PathPrefixedAdapter putFileAs возвращает
        // путь УЖЕ С ПРЕФИКСОМ 'storage/'. И ->url() base тоже заканчивается
        // на '/storage'. Без обрезки получим .../storage/storage/post-covers/...
        // (двойной storage). Срезаем дубликат:
        $cleanPath = preg_replace('#^storage/#', '', $path);
        $publicUrl = Storage::disk('public')->url($cleanPath);

        return response()->json([
            'url'  => $publicUrl,
            'path' => $path,
        ]);
    }

    // ───────────── helpers ─────────────

    private function guardFrozen($user): void
    {
        if ($user->isFrozen()) {
            abort(403, 'Аккаунт заморожен. Создавать посты сейчас нельзя.');
        }
    }

    private function validatePost(Request $request): array
    {
        return $request->validate([
            'title'        => 'sometimes|nullable|string|max:200',
            'body'         => 'required|string|min:10|max:50000',
            'cover_url'    => 'sometimes|nullable|string|max:500',
            'game_id'      => 'sometimes|nullable|integer|exists:games,id',
            'tags'         => 'required|array|min:1|max:5',
            'tags.*'       => 'string|min:2|max:30',
            'visibility'   => 'sometimes|in:public,followers,unlisted',
            'publish_now'  => 'sometimes|boolean',
        ], [
            'body.required' => 'Тело поста обязательно.',
            'body.min'      => 'Слишком короткий пост (минимум 10 символов).',
            'tags.required' => 'Укажите минимум один тег.',
            'tags.min'      => 'Минимум 1 тег.',
            'tags.max'      => 'Максимум 5 тегов.',
        ]);
    }
}
