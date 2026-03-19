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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('entry_number', 50)->index(); // JE-2026-000001
            $table->date('entry_date')->index();
            $table->string('source', 30)->nullable()->index(); // purchase, sale, payment, opening, adjustment...
            $table->string('reference_type', 80)->nullable()->index(); // App\Models\PurchaseInvoice
            $table->unsignedBigInteger('reference_id')->nullable()->index(); // invoice id
            $table->text('description')->nullable();
            $table->decimal('total_debit', 18, 2)->default(0);
            $table->decimal('total_credit', 18, 2)->default(0);
            $table->string('status', 20)->default('posted')->index(); // draft/posted/reversed
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable()->index();
            $table->unsignedBigInteger('reversed_entry_id')->nullable()->index();
            $table->timestamps();
            $table->unique(['user_id', 'entry_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
