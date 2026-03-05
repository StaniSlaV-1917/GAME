<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('genre', 50);
            $table->string('platform', 50);
            $table->decimal('price', 10, 2);
            $table->decimal('rating', 3, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('image', 100)->nullable();
            $table->string('trailer_url', 255)->nullable(); // Добавлено поле для трейлера
            $table->string('stopgame_url_code', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
