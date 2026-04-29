<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 / Batch C — privacy toggle для библиотеки игр пользователя.
 *
 * library_public:
 *   true (default) — все могут видеть купленные игры на /u/:username
 *   false          — библиотека видна только самому юзеру
 *
 * UI настройка появится в Settings (Profile → Личные данные).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'library_public')) {
                $table->boolean('library_public')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('library_public');
        });
    }
};
