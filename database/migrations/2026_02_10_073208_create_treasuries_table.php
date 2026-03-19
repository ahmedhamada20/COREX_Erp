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
        Schema::create('treasuries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete()->cascadeOnUpdate();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_master')->default(false)->comment('الخزنة الرئيسية');
            $table->unsignedBigInteger('last_payment_receipt_no')->default(0)->comment('رقم آخر إيصال للصرف');
            $table->unsignedBigInteger('last_collection_receipt_no')->default(0)->comment('رقم آخر إيصال للتحصيل');
            $table->date('last_reconciled_at')->nullable();
            $table->boolean('status')->default(true);
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->unique(['user_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasuries');
    }
};
