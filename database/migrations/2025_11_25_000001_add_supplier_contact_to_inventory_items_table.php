<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('supplier_email')->nullable()->after('supplier');
            $table->string('supplier_phone', 50)->nullable()->after('supplier_email');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['supplier_email', 'supplier_phone']);
        });
    }
};
