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
        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete()->cascadeOnUpdate();
            $table->decimal('quantity', 18, 4);
            $table->decimal('price', 18, 4);
            $table->decimal('discount', 18, 4)->default(0);
            $table->decimal('vat', 18, 4)->default(0);
            $table->decimal('total', 18, 4);
            $table->decimal('cost_price', 18, 4)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
    }
};
