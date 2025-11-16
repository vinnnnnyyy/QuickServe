<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->integer('stock')->default(0);
            $table->unsignedInteger('min_stock_level')->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_value', 12, 2)->default(0);
            $table->string('status')->default('in_stock');
            $table->string('status_color')->nullable();
            $table->string('supplier')->nullable();
            $table->string('sku')->nullable()->unique();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
