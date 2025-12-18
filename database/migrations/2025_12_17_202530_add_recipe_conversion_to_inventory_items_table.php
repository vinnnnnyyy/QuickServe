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
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('recipe_unit')->nullable()->after('unit'); // e.g., 'scoop', 'pump'
            $table->decimal('conversion_factor', 10, 4)->nullable()->after('recipe_unit'); // e.g., 15 (1 scoop = 15 base units)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['recipe_unit', 'conversion_factor']);
        });
    }
};
