<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('categories')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();

            $table->unsignedInteger('price');               // money in cents
            $table->string('temperature', 10);              // Hot / Cold / Both
            $table->string('prep_time')->nullable();
            $table->json('size_labels')->nullable();        // ["Small","Medium","Large"]
            $table->boolean('featured')->default(false);
            $table->boolean('popular')->default(false);
            $table->boolean('available')->default(true);
            $table->string('image_path')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft','published','archived'])->default('published');

            $table->foreignId('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->timestamps();

            // Common filters
            $table->index(['available','status']);
            $table->index('category_id');
            $table->index('featured');
            $table->index('popular');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};