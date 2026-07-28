<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL: add 'returned' to the existing ENUM
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','confirmed','processing','ready','shipped','delivered','cancelled','returned') DEFAULT 'pending'");
        }
        // SQLite: the 'returned' value was already included in the CREATE TABLE (migration 0007)

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'returned_at')) {
                $table->timestamp('returned_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('returned_at');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','confirmed','processing','ready','shipped','delivered','cancelled') DEFAULT 'pending'");
        }
    }
};
