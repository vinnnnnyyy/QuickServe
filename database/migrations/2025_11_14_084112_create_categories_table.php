<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('scope');          // 'menu', 'inventory', 'both' …
            $table->timestamps();

            $table->unique(['name', 'scope']);
            $table->index('scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};