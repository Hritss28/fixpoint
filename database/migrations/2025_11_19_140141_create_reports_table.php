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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // sales, inventory, receivables, etc.
            $table->string('format')->default('pdf'); // pdf, excel, csv, html
            $table->string('date_range_type')->default('this_month');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('cascade');
            $table->json('product_category')->nullable();
            $table->boolean('include_charts')->default(true);
            $table->boolean('group_by_category')->default(false);
            $table->boolean('show_totals')->default(true);
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency')->nullable();
            $table->time('schedule_time')->nullable();
            $table->json('email_recipients')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
