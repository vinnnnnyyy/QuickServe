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
        Schema::table('cart_items', function (Blueprint $table) {
            // Drop the old incorrect foreign key pointing to 'items'
            // The default naming convention is table_column_foreign
            $table->dropForeign(['product_id']);
            
            // Add the new correct foreign key pointing to 'menu_items'
            $table->foreign('product_id')
                  ->references('id')
                  ->on('menu_items')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreignId('product_id')->constrained('items')->cascadeOnDelete();
        });
    }
};
