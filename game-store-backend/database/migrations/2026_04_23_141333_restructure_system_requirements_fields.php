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
        Schema::table('games', function (Blueprint $table) {
            // Remove old text field
            $table->dropColumn('system_requirements');

            // Add separate structured fields
            $table->string('os_requirements')->nullable()->after('description');
            $table->string('processor_requirements')->nullable()->after('os_requirements');
            $table->string('ram_requirements')->nullable()->after('processor_requirements');
            $table->string('graphics_requirements')->nullable()->after('ram_requirements');
            $table->string('storage_requirements')->nullable()->after('graphics_requirements');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Remove structured fields
            $table->dropColumn([
                'os_requirements',
                'processor_requirements',
                'ram_requirements',
                'graphics_requirements',
                'storage_requirements'
            ]);

            // Add back old text field
            $table->text('system_requirements')->nullable()->after('description');
        });
    }
};
