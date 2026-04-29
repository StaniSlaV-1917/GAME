<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Комментарии к постам.
 *
 * Phase 2 / Batch D.
 *
 * Иерархия — через parent_id self-reference. Глубина считается
 * при создании комментария (= depth родителя + 1). Frontend
 * сворачивает глубокие ветки YouTube-style по depth.
 */
class CommentController extends Controller
{
    /**
     * GET /api/posts/{postId}/comments
     * Возвращает плоский список комментариев. Frontend строит дерево
     * по parent_id.
     */
    public function index(int $postId)
    {
        $post = Post::published()->findOrFail($postId);

        $comments = Comment::where('post_id', $post->id)
            ->approved()
            ->with('author:id,fullname,username,avatar,role')
            ->orderBy('created_at')
            ->get([
                'id', 'post_id', 'parent_id', 'author_id',
                'body', 'depth', 'reaction_count',
                'created_at', 'updated_at',
            ]);

        return response()->json([
            'data'  => $comments,
            'total' => $comments->count(),
        ]);
    }

    /**
     * POST /api/posts/{postId}/comments
     * auth + frozen + throttle (на route).
     */
    public function store(Request $request, int $postId)
    {
        $user = $request->user();
        $this->guardFrozen($user);

        $post = Post::published()->findOrFail($postId);

        if ($post->is_locked) {
            abort(403, 'Комментарии к этому посту закрыты.');
        }

        $data = $request->validate([
            'body'      => 'required|string|min:1|max:5000',
            'parent_id' => 'sometimes|nullable|integer|exists:comments,id',
        ], [
            'body.required' => 'Напишите что-нибудь.',
            'body.max'      => 'Слишком длинный комментарий (макс. 5000 символов).',
        ]);

        // Глубина = depth родителя + 1 (если есть). Защита от depth>20:
        // больше — это уже DOS-эксплуатация цепочкой реплаев.
        $depth = 0;
        if (!empty($data['parent_id'])) {
            $parent = Comment::findOrFail($data['parent_id']);
            if ($parent->post_id !== $post->id) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Родительский коммент из другого поста.'],
                ]);
            }
            $depth = min($parent->depth + 1, 20);
        }

        // Создаём в транзакции с инкрементом счётчика на post — чтобы
        // не получить race-condition если 2 коммента приходят одновременно.
        $comment = DB::transaction(function () use ($user, $post, $data, $depth) {
            $c = Comment::create([
                'post_id'           => $post->id,
                'parent_id'         => $data['parent_id'] ?? null,
                'author_id'         => $user->id,
                'body'              => $data['body'],
                'depth'             => $depth,
                'moderation_status' => in_array($user->role, ['admin', 'manager'])
                                        ? 'approved' : 'approved',  // см. ниже
            ]);

            // Денормализованный счётчик на post (без race)
            Post::where('id', $post->id)->increment('comment_count');

            return $c;
        });

        $comment->load('author:id,fullname,username,avatar,role');

        Log::info('[Comment] created', [
            'comment_id' => $comment->id,
            'post_id'    => $post->id,
            'author_id'  => $user->id,
            'depth'      => $depth,
        ]);

        return response()->json($comment, 201);
    }

    /**
     * PUT /api/comments/{id} — редактировать свой коммент.
     */
    public function update(Request $request, int $id)
    {
        $user = $request->user();
        $this->guardFrozen($user);

        $comment = Comment::findOrFail($id);
        if ($comment->author_id !== $user->id) {
            abort(403, 'Только автор может редактировать коммент.');
        }

        $data = $request->validate([
            'body' => 'required|string|min:1|max:5000',
        ]);

        $comment->update(['body' => $data['body']]);
        $comment->load('author:id,fullname,username,avatar,role');

        return response()->json($comment);
    }

    /**
     * DELETE /api/comments/{id} — soft-delete.
     * Автор или админ. Декремент comment_count на posts.
     * Дочерние комменты остаются (parent_id их не меняется,
     * фронт показывает «удалено» вместо тела).
     */
    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $comment = Comment::findOrFail($id);

        if ($comment->author_id !== $user->id && $user->role !== 'admin') {
            abort(403, 'Удалить может только автор или админ.');
        }

        DB::transaction(function () use ($comment) {
            $comment->delete();
            Post::where('id', $comment->post_id)->decrement('comment_count');
        });

        return response()->json(['message' => 'Комментарий удалён.']);
    }

    private function guardFrozen($user): void
    {
        if ($user->isFrozen()) {
            abort(403, 'Аккаунт заморожен. Комментировать сейчас нельзя.');
        }
    }
}
