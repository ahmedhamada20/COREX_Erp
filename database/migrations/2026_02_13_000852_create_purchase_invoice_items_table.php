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
        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('warehouse_name_snapshot')->nullable();
            $table->string('transaction_id')->nullable();
            // كميات/أسعار
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);

            // خصم سطر
            $table->enum('discount_type', ['none', 'percent', 'fixed'])->default('none');
            $table->decimal('discount_rate', 5, 2)->nullable();
            $table->decimal('discount_value', 12, 2)->default(0);

            // ضريبة سطر
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('tax_value', 12, 2)->default(0);

            // محسوبات سطر
            $table->decimal('line_subtotal', 12, 2)->default(0)->comment('qty*unit قبل الخصم');
            $table->decimal('line_total', 12, 2)->default(0)->comment('بعد الخصم + الضريبة');

            $table->date('date')->nullable();
            $table->string('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['purchase_invoice_id']);
            $table->index(['item_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');
    }
};
