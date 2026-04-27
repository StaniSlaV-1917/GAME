<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * v2.0 / Phase 1 / Batch C — таблицы постов, комментов, реакций.
 *
 * Намеренно одна миграция на пять связанных таблиц: между ними FK,
 * атомарность важнее гранулярности.
 *
 * Ничего из существующих таблиц НЕ трогаем — только добавляем новые.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── reactions_palette: глобальный набор разрешённых эмодзи ─────────
        // Управляется модераторами через админку (Phase 8).
        // Авторы постов могут ограничивать набор для своего поста через
        // post_allowed_reactions.
        Schema::create('reactions_palette', function (Blueprint $table) {
            $table->id();
            $table->string('emoji_char', 8)->nullable();        // unicode-эмодзи: '🔥' / '⚔'
            $table->string('emoji_url')->nullable();             // путь на R2 для кастомных
            $table->string('name', 64);                          // отображаемое: «Огонь»
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);         // глобальное вкл/выкл
            $table->boolean('is_default')->default(false);       // в дефолтный allowed-список новых постов
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique('name');
            $table->index('is_active');
        });

        // ── posts: единая сущность для новостей, постов, обзоров, гайдов ──
        // Гибкая модель с JSONB-tags вместо kind-enum'а: один UI, одна
        // модерация, легко расширять (см. project_v2_plan.md A2).
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('author_role', 32)->default('user');  // снэпшот роли на момент публикации
            $table->string('title')->nullable();                 // не обязателен
            $table->text('body');                                // основной контент (markdown)
            $table->string('cover_url')->nullable();             // R2-URL обложки
            $table->foreignId('game_id')
                  ->nullable()
                  ->constrained('games')
                  ->nullOnDelete();                              // пост может быть привязан к игре
            $table->json('tags')->nullable();                    // ['#обзор', '#vpn', '#обсуждение']
            $table->json('regions')->nullable();                 // null=везде, ['RU']=только РФ
            $table->enum('visibility', ['public', 'followers', 'unlisted'])
                  ->default('public');
            $table->enum('moderation_status', ['approved', 'pending', 'rejected', 'hidden'])
                  ->default('pending');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);        // запрет новых комментов
            $table->unsignedInteger('reaction_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('published_at')->nullable();       // null = черновик
            $table->timestamps();
            $table->softDeletes();

            $table->index(['visibility', 'published_at']);       // лента
            $table->index(['moderation_status', 'created_at']);  // очередь модерации
            $table->index(['game_id', 'published_at']);          // посты к игре
            $table->index(['author_id', 'published_at']);        // профиль
            $table->index('is_featured');
        });

        // GIN-индекс на tags только для Postgres — критично для поиска по тегам
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE posts ALTER COLUMN tags TYPE jsonb USING tags::jsonb');
            DB::statement('ALTER TABLE posts ALTER COLUMN regions TYPE jsonb USING regions::jsonb');
            DB::statement('CREATE INDEX posts_tags_gin_idx ON posts USING GIN (tags)');
        }

        // ── comments: иерархические, бесконечная глубина ──────────────────
        // YouTube-style сворачивание делается на фронте по depth-counter'у,
        // схема просто хранит parent_id self-reference.
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')
                  ->constrained('posts')
                  ->cascadeOnDelete();
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('comments')
                  ->cascadeOnDelete();                           // удаление родителя удаляет ветку
            $table->foreignId('author_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->text('body');
            $table->unsignedInteger('reaction_count')->default(0);
            $table->unsignedSmallInteger('depth')->default(0);   // для оптимизации сворачивания
            $table->enum('moderation_status', ['approved', 'pending', 'rejected', 'hidden'])
                  ->default('approved');                          // verified-юзеры без премодерации
            $table->timestamps();
            $table->softDeletes();

            $table->index(['post_id', 'created_at']);
            $table->index('parent_id');
            $table->index('author_id');
            $table->index('moderation_status');
        });

        // ── post_allowed_reactions: per-post ограничение палитры ───────────
        // Если для поста нет записей — считаем что разрешена вся
        // активная глобальная палитра (is_active=true в reactions_palette).
        Schema::create('post_allowed_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')
                  ->constrained('posts')
                  ->cascadeOnDelete();
            $table->foreignId('palette_emoji_id')
                  ->constrained('reactions_palette')
                  ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['post_id', 'palette_emoji_id']);
        });

        // ── reactions: реакции юзеров на посты, комменты, сообщения ────────
        // Полиморфный morph: post / comment / message в Phase 4.
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->morphs('reactable');                         // reactable_type + reactable_id + index
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('palette_emoji_id')
                  ->constrained('reactions_palette')
                  ->cascadeOnDelete();
            $table->timestamps();

            // Один юзер — одна реакция данным эмодзи на конкретный объект
            $table->unique(['reactable_type', 'reactable_id', 'user_id', 'palette_emoji_id'], 'reactions_unique');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
        Schema::dropIfExists('post_allowed_reactions');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('reactions_palette');
    }
};
