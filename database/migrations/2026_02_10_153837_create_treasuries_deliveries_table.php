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
        Schema::create('treasuries_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('shift_id')->nullable()->constrained('user_shifts')->nullOnDelete()->cascadeOnUpdate();
            $table->enum('type', ['collection', 'payment', 'transfer'])->index();
            $table->foreignId('from_treasury_id')->nullable()->constrained('treasuries')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('to_treasury_id')->nullable()->constrained('treasuries')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('counterparty_account_id')->nullable()->constrained('accounts')->nullOnDelete()->cascadeOnUpdate();
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('receipt_no')->nullable();
            $table->date('doc_date')->nullable();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'doc_date']);
            $table->index(['user_id', 'from_treasury_id']);
            $table->index(['user_id', 'to_treasury_id']);
            $table->index(['shift_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasuries_deliveries');
    }
};
