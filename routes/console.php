<?php

use App\Models\Table;
use App\Models\TableSession;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sessions:expire-stale {minutes=30}', function (int $minutes) {
    $expiredSessions = TableSession::whereIn('status', ['active', 'paid_leaving'])
        ->where('last_activity_at', '<', now()->subMinutes($minutes))
        ->get();

    $expiredCount = 0;
    $affectedTables = [];

    foreach ($expiredSessions as $session) {
        $session->update([
            'status' => 'expired',
            'ended_at' => now(),
        ]);
        $affectedTables[$session->table_id] = true;
        $expiredCount++;
    }

    foreach (array_keys($affectedTables) as $tableId) {
        $table = Table::find($tableId);
        if ($table) {
            $activeSessions = TableSession::where('table_id', $tableId)
                ->whereIn('status', ['active', 'paid_leaving'])
                ->count();

            $table->occupied = $activeSessions;
            if ($activeSessions === 0) {
                $table->status = 'available';
            } elseif ($activeSessions >= $table->capacity) {
                $table->status = 'full';
            } else {
                $table->status = 'partial';
            }
            $table->status_changed_at = now();
            $table->save();
        }
    }

    $this->info("Expired {$expiredCount} stale sessions (inactive > {$minutes} minutes)");
})->purpose('Expire stale table sessions');

Schedule::command('sessions:expire-stale 30')->everyFiveMinutes();
