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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('customer_type', ['retail', 'wholesale', 'contractor', 'distributor'])
                ->default('retail')
                ->after('email');
            $table->string('company_name')->nullable()->after('name');
            $table->string('tax_number')->nullable()->after('company_name')->comment('NPWP');
            $table->decimal('credit_limit', 15, 2)->default(0)->after('tax_number');
            $table->integer('payment_term_days')->default(0)->after('credit_limit');
            $table->text('billing_address')->nullable()->after('payment_term_days');
            $table->text('shipping_address')->nullable()->after('billing_address');
            $table->boolean('is_verified')->default(false)->after('shipping_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'customer_type',
                'company_name',
                'tax_number',
                'credit_limit',
                'payment_term_days',
                'billing_address',
                'shipping_address',
                'is_verified'
            ]);
        });
    }
};
