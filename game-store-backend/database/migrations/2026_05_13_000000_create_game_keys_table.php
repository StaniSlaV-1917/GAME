<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_keys', function (Blueprint $table) {
            $table->id();

            $table->foreignId('game_id')
                ->constrained('games')
                ->cascadeOnDelete();

            $table->string('key_code', 255);

            $table->boolean('is_issued')->default(false)->index();

            $table->foreignId('issued_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();
            
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();

            $table->timestamp('issued_at')->nullable();

            $table->timestamps();

            // Один ключ не может быть добавлен дважды к одной игре
            $table->unique(['game_id', 'key_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_keys');
    }
};
