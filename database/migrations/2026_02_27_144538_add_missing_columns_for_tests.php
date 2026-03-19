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
        Schema::table('items', function (Blueprint $table) {
            if (! Schema::hasColumn('items', 'description')) {
                $table->text('description')->nullable();
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'name_ar')) {
                $table->string('name_ar')->nullable();
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'name_ar')) {
                $table->string('name_ar')->nullable();
            }
        });

        Schema::table('accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounts', 'account_number')) {
                $table->string('account_number')->nullable()->index();
            }
            if (! Schema::hasColumn('accounts', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
