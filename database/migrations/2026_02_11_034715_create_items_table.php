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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('items_code');
            $table->string('barcode');
            $table->string('name');

            $table->decimal('price', 10, 2)->comment('السعر القطاعي بوحده الاسايبه');
            $table->decimal('nos_egomania_price', 10, 2)->comment('السعر نص الجمله  الاقطاعي بوحده الاب');
            $table->decimal('egomania_price', 10, 2)->comment('السعر الجمله بوحده الاسايبه');
            $table->decimal('price_retail', 10, 2)->comment('السعر القطاعي بوحده التجزئه');
            $table->decimal('nos_gomla_price_retail', 10, 2)->comment('السعر نص الجمله القطاعي بوحده التجزئه');
            $table->decimal('gomla_price_retail', 10, 2)->comment('السعر  الجمله القطاعي بوحده التجزئه');

            $table->enum('type', ['store', 'consumption', 'custody'])->default('store')->comment('مخزني,عهدة,استهلاكي');
            $table->foreignId('item_category_id')->nullable()->constrained('item_categories')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('item_id')->nullable()->constrained('items')->cascadeOnDelete()->cascadeOnUpdate();
            $table->boolean('does_has_retail_unit')->nullable()->comment('هل للصنف وحده تجزئه');
            $table->string('retail_unit')->nullable()->comment('كود وحده  قياس التجزئه');
            $table->string('unit_id')->nullable()->comment('كود وحده قياس الاب');
            $table->decimal('retail_uom_quintToParent', 10, 2)->nullable();
            $table->boolean('status')->default(false);
            $table->date('date')->nullable();
            $table->string('image')->nullable();
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
        Schema::dropIfExists('items');
    }
};
