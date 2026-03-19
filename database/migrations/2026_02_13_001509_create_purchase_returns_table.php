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
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('transaction_id')->nullable();
            // ربط المرتجع بفاتورة (اختياري لكن مهم)
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete()->cascadeOnUpdate();
            $table->string('purchase_return_code')->unique(); // بدل purchase_returns
            $table->string('return_number')->index();
            $table->date('return_date')->nullable();
            $table->boolean('tax_included')->default(false);
            $table->decimal('subtotal', 12, 2)->default(0)->comment('قبل الضريبة');
            $table->decimal('tax_value', 12, 2)->default(0)->comment('ضريبة');
            $table->decimal('total', 12, 2)->default(0)->comment('الإجمالي النهائي');

            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');

            $table->timestamp('posted_at')->nullable();
            $table->string('posted_by')->nullable();
            $table->text('notes')->nullable();
            $table->date('date')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'supplier_id']);
            $table->index(['status', 'return_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
