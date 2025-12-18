<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique(); // Session identifier (e.g., #1247)
            
            // Table Relationship
            $table->foreignId('table_id')->constrained('tables')->onDelete('cascade');
            
            // Device Information
            $table->string('device_ip')->nullable(); // IP address (e.g., 192.168.1.45)
            $table->string('device_type')->nullable(); // Device type (e.g., mobile, tablet, desktop)
            $table->string('browser')->nullable(); // Browser info (e.g., iPhone Safari, Android Chrome)
            $table->string('user_agent')->nullable(); // Full user agent string
            
            // Session Status
            $table->enum('status', ['active', 'paid_leaving', 'inactive', 'expired', 'terminated'])
                  ->default('active');
            
            // Activity Tracking
            $table->string('current_activity')->nullable(); // e.g., 'Browsing menu', 'Adding items'
            $table->timestamp('last_activity_at')->nullable();
            
            // Session Timing
            $table->timestamp('started_at')->nullable(); // Session start time
            $table->timestamp('ended_at')->nullable(); // Session end time
            $table->unsignedInteger('duration_minutes')->default(0); // Calculated duration
            
            // Connection Information
            $table->string('network_name')->nullable(); // WiFi network (e.g., CafeOrder_WiFi)
            $table->enum('signal_strength', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->enum('connection_type', ['wifi', 'mobile_data', 'ethernet'])->default('wifi');
            
            // Related Orders
            $table->json('order_ids')->nullable(); // Array of order IDs associated with this session
            
            // Payment Information
            $table->boolean('payment_completed')->default(false);
            $table->timestamp('payment_completed_at')->nullable();
            
            // Session Metadata
            $table->json('metadata')->nullable(); // Additional session data
            $table->text('notes')->nullable(); // Admin notes
            
            // Tracking
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('session_id');
            $table->index('table_id');
            $table->index('status');
            $table->index('device_ip');
            $table->index('started_at');
            $table->index(['table_id', 'status']);
            $table->index(['status', 'started_at']);
            $table->index(['payment_completed', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_sessions');
    }
};
