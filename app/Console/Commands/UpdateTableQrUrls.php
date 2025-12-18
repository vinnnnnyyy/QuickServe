<?php

namespace App\Console\Commands;

use App\Models\Table;
use Illuminate\Console\Command;

class UpdateTableQrUrls extends Command
{
    protected $signature = 'tables:update-qr-urls {--ip=192.168.137.1} {--port=8000}';
    protected $description = 'Update all table QR URLs to use qr_token';

    public function handle()
    {
        $ip = $this->option('ip');
        $port = $this->option('port');

        $tables = Table::all();
        
        foreach ($tables as $table) {
            $table->qr_code_url = "http://{$ip}:{$port}/table/{$table->qr_token}";
            $table->save();
            $this->info("Table {$table->number}: {$table->qr_code_url}");
        }

        $this->info("Updated {$tables->count()} tables.");
    }
}
