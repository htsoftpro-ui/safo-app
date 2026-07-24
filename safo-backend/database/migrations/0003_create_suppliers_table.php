<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('company_name_en')->nullable();
            $table->string('commercial_register')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->text('description')->nullable();
            $table->decimal('min_order_amount', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('free_delivery_threshold', 10, 2)->nullable();
            $table->unsignedInteger('delivery_time_hours')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('total_ratings')->default(0);
            $table->unsignedInteger('total_orders')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('working_hours')->nullable();
            $table->json('delivery_areas')->nullable();
            $table->json('payment_methods')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_verified');
            $table->index('is_active');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
