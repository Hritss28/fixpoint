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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'customer_type')) {
                $table->enum('customer_type', ['retail', 'wholesale', 'contractor'])->default('retail')->after('user_id');
            }
            
            if (!Schema::hasColumn('orders', 'payment_term_days')) {
                $table->integer('payment_term_days')->default(0)->after('payment_method');
            }
            
            if (!Schema::hasColumn('orders', 'due_date')) {
                $table->date('due_date')->nullable()->after('payment_term_days');
            }
            
            if (!Schema::hasColumn('orders', 'delivery_note_id')) {
                $table->foreignId('delivery_note_id')->nullable()->after('due_date')->constrained()->onDelete('set null');
            }
            
            if (!Schema::hasColumn('orders', 'project_name')) {
                $table->string('project_name')->nullable()->after('delivery_note_id')->comment('for contractor');
            }
            
            if (!Schema::hasColumn('orders', 'tax_invoice_number')) {
                $table->string('tax_invoice_number')->nullable()->after('project_name')->comment('nomor faktur pajak');
            }
            
            // Check if payment_status column exists and rename/modify if needed
            if (!Schema::hasColumn('orders', 'payment_status_type')) {
                // Only add if there's no conflict
                $table->enum('payment_status_type', ['pending', 'partial', 'paid', 'overdue'])->default('pending')->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_note_id')) {
                $table->dropForeign(['delivery_note_id']);
            }
            
            $columnsToRemove = [
                'customer_type',
                'payment_term_days',
                'due_date',
                'delivery_note_id',
                'project_name',
                'tax_invoice_number',
                'payment_status_type'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
