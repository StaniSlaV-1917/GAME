<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Phase 4 / Batch A — In-app нотификации.
 *
 * Все методы под auth:sanctum (роуты применяют middleware).
 *
 * Семантика:
 *   GET    /api/notifications              — пагинированный список (последние 50)
 *   GET    /api/notifications/unread-count — только число непрочитанных (для bell-badge)
 *   POST   /api/notifications/{id}/read    — пометить одну как прочитанную
 *   POST   /api/notifications/read-all     — пометить все непрочитанные как прочитанные
 *   DELETE /api/notifications/{id}         — удалить (через notifications()->delete)
 *
 * Не используем Resource классы — просто плоский JSON, фронту так удобнее
 * (data — уже распакованный объект из toDatabase).
 */
class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     *
     * Возвращает 50 последних уведомлений + total_unread.
     * data приходит из БД как JSON-string, разбираем его в объект.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $items = $user->notifications()
            ->limit(50)
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => class_basename($n->type),
                'data'       => $n->data,
                'read_at'    => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'data'        => $items,
            'unread_count'=> $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * GET /api/notifications/unread-count
     * Лёгкий poll-эндпоинт для bell-badge в header.
     */
    public function unreadCount(Request $request)
    {
        $count = $request->user()->unreadNotifications()->count();
        return response()->json(['unread_count' => $count]);
    }

    /**
     * POST /api/notifications/{id}/read
     */
    public function markRead(Request $request, string $id)
    {
        $user = $request->user();

        $notif = $user->notifications()->where('id', $id)->first();
        if (!$notif) {
            abort(404, 'Уведомление не найдено.');
        }

        if (is_null($notif->read_at)) {
            $notif->markAsRead();
        }

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'message'      => 'Помечено как прочитанное.',
        ]);
    }

    /**
     * POST /api/notifications/read-all
     */
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'unread_count' => 0,
            'message'      => 'Все уведомления прочитаны.',
        ]);
    }

    /**
     * DELETE /api/notifications/{id}
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $notif = $user->notifications()->where('id', $id)->first();
        if (!$notif) {
            abort(404, 'Уведомление не найдено.');
        }
        $notif->delete();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'message'      => 'Уведомление удалено.',
        ]);
    }
}
