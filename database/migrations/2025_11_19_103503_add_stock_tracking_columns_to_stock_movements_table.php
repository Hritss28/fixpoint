<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->integer('previous_stock')->default(0)->after('notes');
            $table->integer('new_stock')->default(0)->after('previous_stock');
            $table->boolean('is_reserved')->default(false)->after('new_stock');
            $table->enum('adjustment_type', ['increase', 'decrease'])->nullable()->after('is_reserved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['previous_stock', 'new_stock', 'is_reserved', 'adjustment_type']);
        });
    }
};
