<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('reference_number')->nullable();
            
            // Customer Information
            $table->string('customer_name')->nullable();
            $table->string('customer_nickname')->nullable();
            $table->text('customer_notes')->nullable();
            
            // Order Details
            $table->string('table_number')->nullable();
            $table->enum('order_type', ['dine_in', 'takeaway', 'delivery'])->default('dine_in');
            
            // Order Items stored as JSON
            $table->json('items');
            
            // Pricing
            $table->unsignedInteger('subtotal')->default(0);  // in cents
            $table->unsignedInteger('tax')->default(0);        // in cents
            $table->unsignedInteger('service_fee')->default(0); // in cents
            $table->unsignedInteger('delivery_fee')->default(0); // in cents
            $table->unsignedInteger('total')->default(0);      // in cents
            
            // Payment Information
            $table->enum('payment_method', ['cash', 'gcash', 'card', 'paymaya', 'other'])->nullable();
            $table->enum('payment_status', ['unpaid', 'pending', 'paid', 'refunded', 'failed'])->default('unpaid');
            $table->json('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // Order Status
            $table->enum('status', [
                'received',
                'confirmed', 
                'queued',
                'preparing',
                'ready',
                'served',
                'completed',
                'cancelled'
            ])->default('received');
            
            // Device & Session Information
            $table->string('device_ip')->nullable();
            $table->string('device_type')->nullable();
            $table->string('session_id')->nullable();
            
            // Additional Data
            $table->json('original_data')->nullable(); // Store original checkout payload
            $table->text('notes')->nullable();
            $table->text('special_instructions')->nullable();
            
            // Timestamps & Tracking
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('preparing_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for common queries
            $table->index('order_number');
            $table->index('status');
            $table->index('payment_status');
            $table->index('table_number');
            $table->index('created_at');
            $table->index(['status', 'created_at']);
            $table->index(['payment_status', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
