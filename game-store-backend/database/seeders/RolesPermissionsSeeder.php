<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * RolesPermissionsSeeder — создаёт 5 базовых ролей v2.0 и привязывает к ним
 * стартовый набор permissions. Безопасно идемпотентен: использует firstOrCreate,
 * можно прогонять много раз без дубликатов.
 *
 * Иерархия (от меньших прав к большим):
 *   user        — обычный пользователь, по умолчанию для всех
 *   verified    — verified-юзер (X постов / Y дней — присваивается автоматом
 *                 в Phase 2-3, сейчас никому не выдаётся)
 *   moderator   — модератор форума (бан, скрытие постов, чистка чатов)
 *   employee    — сотрудник магазина (старый "manager"): заказы, поддержка
 *   admin       — старейшина: полный доступ
 */
class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Permissions ─────────────────────────────────────────────────────
        // Группируем по доменам. Список не финальный — будет расширяться
        // в Phase 2 (posts/comments/reactions), Phase 4 (chats), Phase 5 (payments).
        $permissions = [
            // Posts & content (Phase 2)
            'posts.create',
            'posts.edit-own',
            'posts.edit-any',
            'posts.delete-own',
            'posts.delete-any',
            'posts.pin',
            'posts.lock',
            'posts.feature',

            // Comments
            'comments.create',
            'comments.delete-own',
            'comments.delete-any',

            // Reactions / palette (Phase 2-8)
            'reactions.use',
            'reactions.palette.manage',

            // Social (Phase 3)
            'follows.use',

            // Chats (Phase 4)
            'chats.dm.use',
            'chats.group.create',
            'chats.public.create',
            'chats.moderate',

            // Mods (Phase 6)
            'mods.post',
            'mods.aggregator.manage',

            // Moderation queue (Phase 8)
            'moderation.queue.review',
            'moderation.reports.handle',
            'moderation.users.manage',
            'moderation.region.manage',

            // Shop / orders (legacy + Phase 5 crypto)
            'shop.purchase',
            'shop.orders.view-own',
            'shop.orders.view-any',
            'shop.orders.manage',
            'shop.payments.view',

            // Admin entities (legacy)
            'admin.games.manage',
            'admin.news.manage',
            'admin.users.manage',
            'admin.employees.manage',
            'admin.reviews.manage',
            'admin.support.handle',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ── Roles ───────────────────────────────────────────────────────────

        // user — базовая, что-то очень минимальное (читать каталог, покупать,
        // оставлять отзывы — это уже даёт)
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $user->syncPermissions([
            'shop.purchase',
            'shop.orders.view-own',
            'reactions.use',
            'follows.use',
            'chats.dm.use',
        ]);

        // verified — после автоматической верификации (Phase 2-3 будет логика).
        // Получает право постить, комментировать, создавать групповые чаты.
        $verified = Role::firstOrCreate(['name' => 'verified', 'guard_name' => 'web']);
        $verified->syncPermissions(array_merge($user->permissions->pluck('name')->all(), [
            'posts.create',
            'posts.edit-own',
            'posts.delete-own',
            'comments.create',
            'comments.delete-own',
            'chats.group.create',
            'mods.post',
        ]));

        // moderator — может банить, скрывать чужой контент, разруливать жалобы
        $moderator = Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'web']);
        $moderator->syncPermissions(array_merge($verified->permissions->pluck('name')->all(), [
            'posts.edit-any',
            'posts.delete-any',
            'posts.pin',
            'posts.lock',
            'comments.delete-any',
            'chats.moderate',
            'moderation.queue.review',
            'moderation.reports.handle',
            'reactions.palette.manage',
        ]));

        // employee — сотрудник магазина (бывший "manager"): заказы, поддержка.
        // НЕ модерирует форум по умолчанию.
        $employee = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $employee->syncPermissions(array_merge($user->permissions->pluck('name')->all(), [
            'shop.orders.view-any',
            'shop.orders.manage',
            'shop.payments.view',
            'admin.support.handle',
            'admin.reviews.manage',
            'posts.create',
            'comments.create',
            'reactions.use',
        ]));

        // admin — полный доступ. Все permissions из таблицы.
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::pluck('name')->all());

        $this->command?->info('Roles & permissions seeded: user, verified, moderator, employee, admin');
    }
}
