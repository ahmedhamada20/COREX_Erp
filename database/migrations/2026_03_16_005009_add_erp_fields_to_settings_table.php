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
        Schema::table('settings', function (Blueprint $table): void {
            $table->string('vat_number')->nullable();
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->date('fiscal_year_start')->nullable();
            $table->string('base_currency', 10)->default('SAR');
            $table->string('invoice_prefix', 20)->nullable();
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('enable_inventory_tracking')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn([
                'vat_number', 'vat_rate', 'fiscal_year_start',
                'base_currency', 'invoice_prefix', 'decimal_places',
                'enable_inventory_tracking',
            ]);
        });
    }
};
