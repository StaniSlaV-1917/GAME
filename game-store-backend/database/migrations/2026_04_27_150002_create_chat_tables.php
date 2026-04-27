<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v2.0 / Phase 1 / Batch C — чаты всех типов.
 *
 * Три типа чатов в одной модели (chat_rooms.type):
 *   direct  — DM 1-на-1
 *   group   — групповой чат, создаётся юзером, может быть приватным
 *             или public (по приглашению / по ссылке)
 *   public  — публичный тематический канал, как в Telegram/Discord;
 *             создаётся модераторами или verified-юзерами
 *
 * Сообщения общие для всех типов через chat_room_id FK.
 * Phase 4 оживляет это через Reverb (broadcast events).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── chat_rooms: контейнер для всех чатов ──────────────────────────
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['direct', 'group', 'public']);
            $table->string('name')->nullable();                  // direct = null, group/public = required
            $table->string('description')->nullable();           // для public-каналов с темой
            $table->string('avatar_url')->nullable();            // R2-URL аватара чата
            $table->foreignId('created_by')
                  ->nullable()                                   // direct может создаваться неявно
                  ->constrained('users')
                  ->nullOnDelete();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('last_message_at')->nullable();    // для сортировки списка чатов
            $table->timestamps();

            $table->index(['type', 'last_message_at']);          // listing
            $table->index('created_by');
        });

        // ── messages: сообщения всех чатов ────────────────────────────────
        // Self-FK для reply_to_message_id (цитирование/треды как в Slack).
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_room_id')
                  ->constrained('chat_rooms')
                  ->cascadeOnDelete();
            $table->foreignId('sender_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->text('body');
            $table->json('attachments')->nullable();             // [{type:'image',url:'...'}, ...]
            $table->foreignId('reply_to_message_id')
                  ->nullable()
                  ->constrained('messages')
                  ->nullOnDelete();
            $table->boolean('is_edited')->default(false);
            $table->enum('moderation_status', ['approved', 'pending', 'rejected', 'hidden'])
                  ->default('approved');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['chat_room_id', 'created_at']);       // загрузка истории
            $table->index('sender_id');
            $table->index('moderation_status');
        });

        // ── chat_room_participants: членство в чате ───────────────────────
        // last_read_message_id — указатель «прочитано до этого сообщения»
        // для unread-counter. Не FK, потому что сообщение может удалиться,
        // а указатель должен жить дальше (мягкая ссылка по bigint).
        Schema::create('chat_room_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_room_id')
                  ->constrained('chat_rooms')
                  ->cascadeOnDelete();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->enum('role', ['owner', 'admin', 'member', 'muted'])
                  ->default('member');                           // per-room mod-роль
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();            // null = всё ещё в чате
            $table->boolean('is_pinned')->default(false);        // прикреплено в списке чатов юзера
            $table->boolean('is_muted')->default(false);         // notifications off
            $table->timestamps();

            $table->unique(['chat_room_id', 'user_id']);
            $table->index('user_id');                            // "мои чаты"
            $table->index(['chat_room_id', 'role']);             // "кто owner/admin"
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_room_participants');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('chat_rooms');
    }
};
