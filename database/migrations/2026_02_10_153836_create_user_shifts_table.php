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
        Schema::create('user_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('actor_user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('treasury_id')->constrained('treasuries')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamp('opened_at');
            $table->string('transaction_id')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('closing_expected', 15, 2)->default(0);
            $table->decimal('closing_actual', 15, 2)->nullable();
            $table->decimal('difference', 15, 2)->default(0);
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->string('closed_by')->nullable();
            $table->index(['user_id', 'actor_user_id', 'status']);
            $table->index(['user_id', 'treasury_id', 'status']);
            $table->index(['treasury_id', 'status']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_shifts');
    }
};
