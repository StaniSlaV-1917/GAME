<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица sessions для SESSION_DRIVER=database.
 * В Laravel 10 эта миграция не входит по умолчанию — публикуется через
 * `php artisan session:table`. Добавлено вручную при настройке Fly-деплоя
 * (см. game-store-backend/fly.toml SESSION_DRIVER="database").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
