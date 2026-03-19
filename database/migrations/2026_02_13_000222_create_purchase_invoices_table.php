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
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('purchase_invoice_code')->unique(); // PI-XXXX
            $table->string('invoice_number')->index();
            $table->date('invoice_date')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('purchase_order_id')->nullable()->comment('امر مشتريات');
            // Cash / Credit
            $table->enum('payment_type', ['cash', 'credit'])->default('credit');
            $table->date('due_date')->nullable()->comment('تاريخ الاستحقاق');
            $table->string('currency_code', 3)->nullable()->default('EGP');
            $table->decimal('exchange_rate', 12, 6)->nullable()->default(1);
            $table->boolean('tax_included')->default(false);
            $table->decimal('subtotal_before_discount', 12, 2)->default(0)->comment('إجمالي قبل الخصم وقبل الضريبة');
            $table->enum('discount_type', ['none', 'percent', 'fixed'])->default('none');
            $table->decimal('discount_rate', 5, 2)->nullable()->comment('% لو خصم نسبة');
            $table->decimal('discount_value', 12, 2)->default(0)->comment('قيمة الخصم النهائية');
            $table->decimal('shipping_cost', 12, 2)->default(0)->comment('شحن/نقل مشتريات');
            $table->decimal('other_charges', 12, 2)->default(0)->comment('مصاريف أخرى');
            $table->decimal('subtotal', 12, 2)->default(0)->comment('بعد الخصم وقبل الضريبة');
            $table->decimal('tax_value', 12, 2)->default(0)->comment('ضريبة');
            $table->decimal('total', 12, 2)->default(0)->comment('الإجمالي النهائي');
            $table->decimal('paid_amount', 12, 2)->default(0)->comment('المدفوع (محسوب/مُحدّث)');
            $table->decimal('remaining_amount', 12, 2)->default(0)->comment('المتبقي');
            $table->enum('status', ['draft', 'posted', 'paid', 'partial', 'cancelled'])->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->string('posted_by')->nullable();
            $table->text('notes')->nullable();
            $table->date('date')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'supplier_id']);
            $table->index(['status', 'invoice_date']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
