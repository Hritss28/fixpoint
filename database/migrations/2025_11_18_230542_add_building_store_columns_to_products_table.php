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
        Schema::table('products', function (Blueprint $table) {
            $table->string('unit')->default('pcs')->after('price')->comment('satuan jual');
            $table->integer('min_order_qty')->default(1)->after('unit');
            $table->decimal('wholesale_price', 15, 2)->nullable()->after('min_order_qty');
            $table->decimal('contractor_price', 15, 2)->nullable()->after('wholesale_price');
            $table->foreignId('supplier_id')->nullable()->after('contractor_price')->constrained()->onDelete('set null');
            $table->integer('reorder_level')->default(10)->after('stock')->comment('minimum stock level');
            $table->string('location')->nullable()->after('reorder_level')->comment('warehouse location');
            $table->string('barcode')->nullable()->unique()->after('location');
            $table->boolean('is_bulk_only')->default(false)->after('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn([
                'unit',
                'min_order_qty',
                'wholesale_price',
                'contractor_price',
                'supplier_id',
                'reorder_level',
                'location',
                'barcode',
                'is_bulk_only'
            ]);
        });
    }
};
