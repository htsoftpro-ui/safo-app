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
            $table->string('order_number', 50)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('address_id')->nullable()->nullOnDelete();

            // Status
            $table->enum('status', [
                'pending',      // awaiting supplier confirmation
                'confirmed',    // supplier accepted
                'processing',   // being prepared
                'ready',        // ready for pickup/delivery
                'shipped',      // out for delivery
                'delivered',    // customer received
                'cancelled',    // cancelled
                'returned',     // returned after delivery
            ])->default('pending');

            // Financials
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);

            // Payment
            $table->enum('payment_method', ['cash', 'credit', 'wallet'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');

            // Delivery — snapshot of address at order time
            $table->text('delivery_address')->nullable();
            $table->decimal('delivery_latitude', 10, 8)->nullable();
            $table->decimal('delivery_longitude', 11, 8)->nullable();
            $table->text('delivery_notes')->nullable();

            // Timestamps
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            // Cancellation
            $table->text('cancellation_reason')->nullable();
            $table->enum('cancelled_by', ['customer', 'supplier', 'admin'])->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('supplier_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');

            // Composite for common queries
            $table->index(['user_id', 'status']);
            $table->index(['supplier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
