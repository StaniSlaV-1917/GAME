<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 / Batch B — email-дубли in-app нотификаций.
 *
 * Добавляем 4 boolean prefs (default true). Юзер может отключить
 * email-копию для каждого типа события через /profile.
 *
 * Сами database-нотификации не отключаются — bell в шапке всегда
 * работает. Эти prefs управляют только дубликатом на email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'notify_email_comment')) {
                $table->boolean('notify_email_comment')->default(true);
            }
            if (!Schema::hasColumn('users', 'notify_email_reply')) {
                $table->boolean('notify_email_reply')->default(true);
            }
            if (!Schema::hasColumn('users', 'notify_email_reaction')) {
                $table->boolean('notify_email_reaction')->default(true);
            }
            if (!Schema::hasColumn('users', 'notify_email_follower')) {
                $table->boolean('notify_email_follower')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_email_comment',
                'notify_email_reply',
                'notify_email_reaction',
                'notify_email_follower',
            ]);
        });
    }
};
