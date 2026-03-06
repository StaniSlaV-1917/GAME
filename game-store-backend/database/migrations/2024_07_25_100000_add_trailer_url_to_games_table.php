
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('games', function (Blueprint $table) {
            // Добавляем поле для URL трейлера после поля 'image'
            // Поле может быть пустым (nullable)
            $table->string('trailer_url')->nullable()->after('image');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('games', function (Blueprint $table) {
            // Удаляем поле, если потребуется откат миграции
            $table->dropColumn('trailer_url');
        });
    }
};
