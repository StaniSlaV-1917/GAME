<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('stopgame_url_code');
            $table->boolean('is_new')->default(false)->after('is_featured');

            $table->decimal('old_price', 10, 2)->nullable()->after('price');
            $table->unsignedTinyInteger('discount_percent')->nullable()->after('old_price');

            $table->unsignedSmallInteger('release_year')->nullable()->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn([
                'is_featured',
                'is_new',
                'old_price',
                'discount_percent',
                'release_year',
            ]);
        });
    }
};
