<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable()->unique()->after('qr_code_file_path');
        });

        $tables = \App\Models\Table::all();
        foreach ($tables as $t) {
            $t->qr_token = Str::random(32);
            $t->save();
        }
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn('qr_token');
        });
    }
};
