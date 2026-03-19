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
        Schema::create('fixed_asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('period_key', 20)->index();
            $table->date('period_from');
            $table->date('period_to');
            $table->decimal('amount', 18, 2)->default(0);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->string('status', 20)->default('posted')->index();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'fixed_asset_id', 'period_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_depreciations');
    }
};
