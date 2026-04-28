<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountDeletedMail;
use App\Mail\UserBannedMail;
use App\Mail\UserFrozenMail;
use App\Mail\UserUnbannedMail;
use App\Mail\UserUnfrozenMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Админ-контроллер для модерации пользователей.
 *
 * Phase 1.6 / Batch B — РЕДАКТИРОВАНИЕ ЛИЧНЫХ ДАННЫХ ЗАПРЕЩЕНО.
 * Метод update() удалён — админы могут только:
 *   • просматривать всю информацию о юзере (index, show)
 *   • менять роль (updateRole)
 *   • банить / разбанивать  (ban / unban)
 *   • замораживать / размораживать  (freeze / unfreeze)
 *   • soft-delete  (destroy)
 *
 * При любом модерационном действии — отправляем email-уведомление
 * пользователю (Batch C).
 */
class AdminUserController extends Controller
{
    /**
     * Список всех юзеров — админ видит ВСЁ (включая email/phone/username/
     * статусы модерации). Это для отображения, не для редактирования.
     */
    public function index()
    {
        $users = User::select(
                'id', 'fullname', 'username', 'email', 'phone', 'avatar',
                'role', 'reg_date',
                'banned_at', 'ban_reason', 'frozen_at', 'freeze_reason'
            )
            ->orderByDesc('id')
            ->get();

        return response()->json($users);
    }

    /**
     * Сменить роль (user/manager/admin). Единственная «редактирующая»
     * операция, которая осталась — потому что роль это не личные данные.
     */
    public function updateRole(Request $request, int $id)
    {
        $data = $request->validate([
            'role' => 'required|in:user,manager,admin',
        ]);

        DB::table('users')->where('id', $id)->update(['role' => $data['role']]);

        $user = User::findOrFail($id);

        return response()->json([
            'message' => 'Роль пользователя обновлена',
            'user'    => $this->userPayload($user),
        ]);
    }

    /**
     * Забанить пользователя.
     * Заблокирует логин (AuthController::login возвращает 403).
     * Заодно отзываются все Sanctum-токены — текущие сессии прерываются.
     */
    public function ban(Request $request, int $id)
    {
        $data = $request->validate([
            'reason' => 'required|string|min:3|max:1000',
        ]);

        $user = User::findOrFail($id);

        // Запрет банить других админов через API (защита от war'а админов).
        // Только через ручную операцию в БД.
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Бан админа возможен только через прямой доступ к БД.',
            ], 403);
        }

        $user->banned_at  = now();
        $user->ban_reason = $data['reason'];
        $user->save();

        // Отзываем все токены — все активные сессии прерываются.
        $user->tokens()->delete();

        // Email-уведомление (Batch C добавит UserBannedMail)
        $this->notifyModerationAction($user, 'banned', $data['reason']);

        Log::info("Admin banned user", [
            'user_id'   => $user->id,
            'reason'    => $data['reason'],
            'admin_id'  => $request->user()->id,
        ]);

        return response()->json([
            'message' => "Пользователь {$user->fullname} заблокирован.",
            'user'    => $this->userPayload($user->fresh()),
        ]);
    }

    /**
     * Снять бан.
     */
    public function unban(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if (!$user->isBanned()) {
            return response()->json(['message' => 'Пользователь не забанен.'], 422);
        }

        $user->banned_at  = null;
        $user->ban_reason = null;
        $user->save();

        $this->notifyModerationAction($user, 'unbanned');

        Log::info("Admin unbanned user", [
            'user_id'  => $user->id,
            'admin_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => "Бан с пользователя {$user->fullname} снят.",
            'user'    => $this->userPayload($user->fresh()),
        ]);
    }

    /**
     * Заморозить — мягче бана. Юзер может логиниться и читать, но не
     * может создавать контент (посты/комменты/реакции — проверка в
     * соответствующих контроллерах).
     */
    public function freeze(Request $request, int $id)
    {
        $data = $request->validate([
            'reason' => 'required|string|min:3|max:1000',
        ]);

        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Заморозка админа невозможна.',
            ], 403);
        }

        $user->frozen_at     = now();
        $user->freeze_reason = $data['reason'];
        $user->save();

        $this->notifyModerationAction($user, 'frozen', $data['reason']);

        Log::info("Admin froze user", [
            'user_id'  => $user->id,
            'reason'   => $data['reason'],
            'admin_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => "Пользователь {$user->fullname} заморожен.",
            'user'    => $this->userPayload($user->fresh()),
        ]);
    }

    /**
     * Снять заморозку.
     */
    public function unfreeze(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if (!$user->isFrozen()) {
            return response()->json(['message' => 'Пользователь не заморожен.'], 422);
        }

        $user->frozen_at     = null;
        $user->freeze_reason = null;
        $user->save();

        $this->notifyModerationAction($user, 'unfrozen');

        Log::info("Admin unfroze user", [
            'user_id'  => $user->id,
            'admin_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => "Заморозка с пользователя {$user->fullname} снята.",
            'user'    => $this->userPayload($user->fresh()),
        ]);
    }

    /**
     * Удалить пользователя (фактический DELETE — у нас в users нет
     * deleted_at колонки, так что это hard delete с каскадом по FK).
     * Существующее поведение — НЕ изменено.
     */
    public function destroy(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Удаление админа невозможно через API.',
            ], 403);
        }

        // Email-уведомление до удаления (после удаления некуда писать)
        $this->notifyModerationAction($user, 'deleted');

        $user->delete();

        Log::info("Admin deleted user", [
            'user_id'  => $id,
            'admin_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Пользователь удалён.',
        ]);
    }

    /**
     * Унифицированный payload юзера для ответов админ-эндпоинтов.
     */
    private function userPayload(User $user): array
    {
        return $user->only([
            'id', 'fullname', 'username', 'email', 'phone', 'avatar', 'role',
            'reg_date', 'banned_at', 'ban_reason', 'frozen_at', 'freeze_reason',
        ]);
    }

    /**
     * Отправляет email-уведомление пользователю при модерационном действии.
     * Sync-отправка (без очереди) — действия модерации редкие, fail-fast OK.
     * При ошибке отправки логируем но не падаем (юзер всё равно забанен/etc).
     */
    private function notifyModerationAction(User $user, string $action, ?string $reason = null): void
    {
        if (!$user->email) {
            Log::warning("[ModNotify] {$action}: user_id={$user->id} нет email — пропускаем уведомление");
            return;
        }

        Log::info("[ModNotify] {$action} → {$user->email}", [
            'user_id' => $user->id,
            'reason'  => $reason,
        ]);

        $userName = $user->fullname ?: $user->username ?: 'воин';
        $now      = now()->format('d.m.Y H:i');

        try {
            $mail = match ($action) {
                'banned'    => new UserBannedMail($userName, $reason ?? 'Причина не указана', $now),
                'unbanned'  => new UserUnbannedMail($userName),
                'frozen'    => new UserFrozenMail($userName, $reason ?? 'Причина не указана', $now),
                'unfrozen'  => new UserUnfrozenMail($userName),
                'deleted'   => new AccountDeletedMail($userName),
                default     => null,
            };

            if ($mail) {
                Mail::to($user->email)->send($mail);
            }
        } catch (\Throwable $e) {
            // Падение SMTP не должно ломать админ-действие — модерация
            // приоритетнее уведомления.
            Log::error("[ModNotify] mail failed for {$user->email}: " . $e->getMessage(), [
                'action'  => $action,
                'user_id' => $user->id,
            ]);
        }
    }
}
