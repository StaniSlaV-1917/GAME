<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // int, AI
            $table->text('fullname')->nullable();
            $table->text('email')->nullable();
            $table->text('phone')->nullable();
            $table->string('password', 255);
            $table->enum('role', ['user', 'manager', 'admin'])->default('user');
            $table->dateTime('reg_date')->useCurrent()->nullable();
            $table->string('email_hash', 64)->nullable();
            $table->string('phone_hash', 64)->nullable();

            $table->unique('email_hash');
            $table->unique('phone_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
