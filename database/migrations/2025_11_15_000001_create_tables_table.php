<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->unique(); // Table number
            
            // Location Information
            $table->enum('location', ['Indoor', 'Outdoor', 'Patio', 'Bar'])->default('Indoor');
            
            // Capacity & Occupancy
            $table->unsignedInteger('capacity')->default(2); // Maximum seats
            $table->unsignedInteger('occupied')->default(0); // Current occupied seats
            
            // Table Status
            $table->enum('status', ['available', 'partial', 'full', 'cleaning', 'reserved', 'out_of_service'])
                  ->default('available');
            
            // QR Code
            $table->string('qr_code')->unique(); // QR code identifier
            $table->text('qr_code_url')->nullable(); // Full QR code URL
            $table->string('qr_code_file_path')->nullable(); // Path to generated QR code image
            
            // Additional Information
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Extra data like amenities, features
            
            // Status tracking
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_cleaned_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('number');
            $table->index('location');
            $table->index('status');
            $table->index('qr_code');
            $table->index(['location', 'status']);
            $table->index(['status', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
