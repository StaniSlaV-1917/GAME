<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * UserRolesBackfillSeeder — переводит существующих юзеров со старой системы
 * (поле users.role строкой) в новую spatie-RBAC.
 *
 * Mapping:
 *   admin    (старый) → admin    (spatie)
 *   manager  (старый) → employee (spatie) — новое название «сотрудник магазина»
 *   user     (старый) → user     (spatie)
 *   NULL/иное          → user    (spatie) — fallback
 *
 * Поле users.role СОХРАНЯЕТСЯ — это legacy fallback, текущий
 * AdminMiddleware/ManagerMiddleware и фронт-Vue до сих пор его читают.
 * Удалим в Phase 2 после полного перехода на spatie проверки.
 *
 * Идемпотентен: assignRole пропускает повторные присвоения, syncRoles —
 * заменяет, тут используем assignRole + предварительная проверка.
 */
class UserRolesBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $stats = ['admin' => 0, 'employee' => 0, 'user' => 0, 'skipped' => 0];

        User::chunk(100, function ($users) use (&$stats) {
            foreach ($users as $user) {
                $oldRole = $user->role;
                $newRole = match ($oldRole) {
                    'admin'   => 'admin',
                    'manager' => 'employee',
                    default   => 'user',
                };

                // Если spatie-роль уже есть — пропускаем, иначе назначаем
                if ($user->hasRole($newRole)) {
                    $stats['skipped']++;
                    continue;
                }

                $user->assignRole($newRole);
                $stats[$newRole]++;
            }
        });

        $this->command?->info(sprintf(
            'User roles backfilled: %d admins, %d employees, %d users (%d skipped)',
            $stats['admin'], $stats['employee'], $stats['user'], $stats['skipped']
        ));
    }
}
