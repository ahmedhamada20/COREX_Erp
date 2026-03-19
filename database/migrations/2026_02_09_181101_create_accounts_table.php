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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('account_type_id')->constrained('account_types')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('parent_account_id')->nullable()->constrained('accounts')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('name');
            $table->string('account_number')->nullable();
            $table->decimal('start_balance', 10, 2)->nullable()->comment('رصيد الافتحتاحي اول المده');
            $table->decimal('current_balance', 10, 2)->nullable();
            $table->string('other_table_id')->nullable()->comment('علشان قدام اي id اكون عارف الادي الي بعت للحسابات');
            $table->text('notes')->nullable();
            $table->boolean('status')->default(false);
            $table->date('date')->nullable();
            $table->string('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
