<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable()->index();
            $table->decimal('price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->string('unit', 50); // كرتون، علبة، كيلو
            $table->unsignedInteger('unit_quantity')->default(1); // 12 قطعة في الكرتون
            $table->unsignedInteger('min_order_quantity')->default(1);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(10);
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('weight_unit', 20)->nullable();
            $table->json('images')->nullable();
            $table->string('thumbnail')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('sales_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('total_ratings')->default(0);
            $table->json('tags')->nullable();
            $table->json('attributes')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('country_of_origin', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('supplier_id');
            $table->index('category_id');
            $table->index('price');
            $table->index('rating');
            $table->index('sales_count');
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('created_at');

            // Composite index for the most common query pattern
            $table->index(['is_active', 'category_id', 'created_at']);
            $table->index(['is_active', 'supplier_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
