<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_login')->default(true)->after('avatar');
            $table->boolean('notify_order_created')->default(true)->after('notify_login');
            $table->boolean('notify_order_status')->default(true)->after('notify_order_created');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notify_login', 'notify_order_created', 'notify_order_status']);
        });
    }
};
