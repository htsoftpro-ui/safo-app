<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'returned' to the status enum
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','confirmed','processing','ready','shipped','delivered','cancelled','returned') DEFAULT 'pending'");

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('returned_at')->nullable()->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('returned_at');
        });

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','confirmed','processing','ready','shipped','delivered','cancelled') DEFAULT 'pending'");
    }
};
