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
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('products', 'unit')) {
                $table->string('unit')->default('pcs')->after('weight');
            }
            if (!Schema::hasColumn('products', 'min_order_qty')) {
                $table->integer('min_order_qty')->default(1)->after('unit');
            }
            if (!Schema::hasColumn('products', 'wholesale_price')) {
                $table->decimal('wholesale_price', 12, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('products', 'contractor_price')) {
                $table->decimal('contractor_price', 12, 2)->nullable()->after('wholesale_price');
            }
            if (!Schema::hasColumn('products', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null')->after('brand_id');
            }
            if (!Schema::hasColumn('products', 'reorder_level')) {
                $table->integer('reorder_level')->default(10)->after('stock');
            }
            if (!Schema::hasColumn('products', 'location')) {
                $table->string('location')->nullable()->after('reorder_level');
            }
            if (!Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable()->after('sku');
            }
            if (!Schema::hasColumn('products', 'is_bulk_only')) {
                $table->boolean('is_bulk_only')->default(false)->after('is_featured');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
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
