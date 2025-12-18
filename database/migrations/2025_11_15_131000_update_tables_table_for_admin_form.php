<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->json('features')->nullable()->after('description');
            $table->text('notes')->nullable()->after('features');
        });

        DB::statement("ALTER TABLE `tables` MODIFY `location` VARCHAR(50) NOT NULL DEFAULT 'Indoor'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `tables` MODIFY `location` ENUM('Indoor','Outdoor','Patio','Bar') NOT NULL DEFAULT 'Indoor'");

        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn(['features', 'notes']);
        });
    }
};
