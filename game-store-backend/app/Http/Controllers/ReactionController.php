<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\ReactionPalette;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Реакции (эмодзи) на посты и комментарии.
 *
 * Phase 2 / Batch E.
 *
 * Полиморфная связь reactable_type ('App\Models\Post' | 'App\Models\Comment').
 * Один юзер не может ставить одну и ту же реакцию дважды на тот же
 * объект — уникальный индекс reactions_unique в БД.
 */
class ReactionController extends Controller
{
    /**
     * GET /api/reactions/palette
     * Активная палитра (8 эмодзи в Ashenforge-стиле).
     * Используется фронтом для popup-выбора при «+ реакция».
     */
    public function palette()
    {
        $items = ReactionPalette::active()->get([
            'id', 'emoji_char', 'name', 'description', 'is_default', 'sort_order',
        ]);

        return response()->json($items);
    }

    /**
     * POST /api/reactions/toggle
     * auth + frozen + throttle.
     *
     * Body: {
     *   reactable_type: 'post' | 'comment',
     *   reactable_id:   int,
     *   palette_emoji_id: int,
     * }
     *
     * Если реакция уже стоит — удаляем (unreact). Иначе создаём.
     * Денормализованный счётчик `reaction_count` на target обновляем
     * в той же транзакции (без race).
     *
     * Returns: {
     *   action: 'added' | 'removed',
     *   reacted_by_me: bool,
     *   total_count: int,           // всего реакций на target после действия
     *   summary: [{ palette_emoji_id, emoji_char, name, count, reacted_by_me }, ...]
     * }
     */
    public function toggle(Request $request)
    {
        $user = $request->user();
        if ($user->isFrozen()) {
            abort(403, 'Аккаунт заморожен. Реакции сейчас недоступны.');
        }

        $data = $request->validate([
            'reactable_type'   => 'required|in:post,comment',
            'reactable_id'     => 'required|integer',
            'palette_emoji_id' => 'required|integer|exists:reactions_palette,id',
        ]);

        // Развёртываем тип в полное FQCN (как Eloquent его хранит в БД)
        $type = $data['reactable_type'] === 'post'
            ? Post::class
            : Comment::class;

        // Verify target exists и опубликован
        if ($type === Post::class) {
            $target = Post::published()->findOrFail($data['reactable_id']);
            if ($target->is_locked) {
                abort(403, 'Пост закрыт для реакций.');
            }
        } else {
            $target = Comment::approved()->findOrFail($data['reactable_id']);
        }

        // Если у поста есть allowedReactions — проверяем что эмодзи разрешён
        if ($type === Post::class) {
            $allowed = $target->allowedReactions()->pluck('palette_emoji_id');
            if ($allowed->isNotEmpty() && !$allowed->contains($data['palette_emoji_id'])) {
                throw ValidationException::withMessages([
                    'palette_emoji_id' => ['Эта реакция не разрешена для данного поста.'],
                ]);
            }
        }

        // Toggle логика в транзакции
        $action = DB::transaction(function () use ($user, $type, $data, $target) {
            $existing = Reaction::where('reactable_type', $type)
                ->where('reactable_id', $data['reactable_id'])
                ->where('user_id', $user->id)
                ->where('palette_emoji_id', $data['palette_emoji_id'])
                ->first();

            if ($existing) {
                $existing->delete();
                $target->decrement('reaction_count');
                return 'removed';
            }

            Reaction::create([
                'reactable_type'   => $type,
                'reactable_id'     => $data['reactable_id'],
                'user_id'          => $user->id,
                'palette_emoji_id' => $data['palette_emoji_id'],
            ]);
            $target->increment('reaction_count');
            return 'added';
        });

        // Свежий снапшот: общий счётчик + грouped summary
        $target->refresh();
        $summary = $this->summaryForTarget($type, $data['reactable_id'], $user->id);

        return response()->json([
            'action'        => $action,
            'reacted_by_me' => $action === 'added',
            'total_count'   => $target->reaction_count,
            'summary'       => $summary,
        ]);
    }

    /**
     * GET /api/reactions/summary?reactable_type=post&reactable_id=123
     * Получить аггрегированную сводку реакций для одного объекта.
     * Используется на post-view для первичной загрузки.
     */
    public function summary(Request $request)
    {
        $data = $request->validate([
            'reactable_type' => 'required|in:post,comment',
            'reactable_id'   => 'required|integer',
        ]);

        $type = $data['reactable_type'] === 'post' ? Post::class : Comment::class;
        $userId = optional($request->user())->id;

        return response()->json([
            'summary' => $this->summaryForTarget($type, $data['reactable_id'], $userId),
        ]);
    }

    /**
     * Helper: вернуть groupBy реакции по палитре + флаг reacted_by_me
     * для текущего юзера (или null если гость).
     */
    private function summaryForTarget(string $type, int $id, ?int $userId): array
    {
        $rows = DB::table('reactions')
            ->join('reactions_palette', 'reactions.palette_emoji_id', '=', 'reactions_palette.id')
            ->where('reactions.reactable_type', $type)
            ->where('reactions.reactable_id', $id)
            ->select(
                'reactions_palette.id as palette_emoji_id',
                'reactions_palette.emoji_char',
                'reactions_palette.name',
                DB::raw('count(*) as count')
            )
            ->groupBy(
                'reactions_palette.id',
                'reactions_palette.emoji_char',
                'reactions_palette.name',
                'reactions_palette.sort_order'
            )
            ->orderBy('reactions_palette.sort_order')
            ->get();

        // Какие эмодзи поставил текущий юзер на этот объект
        $myEmojis = $userId
            ? DB::table('reactions')
                ->where('reactable_type', $type)
                ->where('reactable_id', $id)
                ->where('user_id', $userId)
                ->pluck('palette_emoji_id')
                ->toArray()
            : [];

        return $rows->map(fn ($r) => [
            'palette_emoji_id' => $r->palette_emoji_id,
            'emoji_char'       => $r->emoji_char,
            'name'             => $r->name,
            'count'            => (int) $r->count,
            'reacted_by_me'    => in_array($r->palette_emoji_id, $myEmojis),
        ])->all();
    }

    /**
     * Public helper для других контроллеров (PostController::show,
     * CommentController::index) — батчевая загрузка summary для
     * множества объектов одного типа.
     *
     * @return array  ['{id}' => [...summary...], ...]
     */
    public static function batchSummary(string $type, array $ids, ?int $userId): array
    {
        if (empty($ids)) return [];

        $rows = DB::table('reactions')
            ->join('reactions_palette', 'reactions.palette_emoji_id', '=', 'reactions_palette.id')
            ->where('reactions.reactable_type', $type)
            ->whereIn('reactions.reactable_id', $ids)
            ->select(
                'reactions.reactable_id',
                'reactions_palette.id as palette_emoji_id',
                'reactions_palette.emoji_char',
                'reactions_palette.name',
                DB::raw('count(*) as count')
            )
            ->groupBy(
                'reactions.reactable_id',
                'reactions_palette.id',
                'reactions_palette.emoji_char',
                'reactions_palette.name',
                'reactions_palette.sort_order'
            )
            ->orderBy('reactions_palette.sort_order')
            ->get()
            ->groupBy('reactable_id');

        $myReactions = $userId
            ? DB::table('reactions')
                ->where('reactable_type', $type)
                ->whereIn('reactable_id', $ids)
                ->where('user_id', $userId)
                ->select('reactable_id', 'palette_emoji_id')
                ->get()
                ->groupBy('reactable_id')
            : collect();

        $result = [];
        foreach ($ids as $id) {
            $rowsForId = $rows->get($id) ?? collect();
            // Безопасный chain: если $myReactions->get() = null, делаем
            // пустой Collection. optional()->pluck() в Laravel вернул бы
            // null, и ->toArray() на null упал бы с TypeError.
            $myEmojis = ($myReactions->get($id) ?? collect())
                ->pluck('palette_emoji_id')
                ->toArray();
            $result[$id] = $rowsForId->map(fn ($r) => [
                'palette_emoji_id' => $r->palette_emoji_id,
                'emoji_char'       => $r->emoji_char,
                'name'             => $r->name,
                'count'            => (int) $r->count,
                'reacted_by_me'    => in_array($r->palette_emoji_id, $myEmojis),
            ])->values()->all();
        }
        return $result;
    }
}
