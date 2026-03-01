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
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('type')->default('Asset'); // Asset, Liability, Revenue, Expense
            $table->boolean('is_default')->default(false);
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->integer('account_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['type', 'is_default']);
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('account_id');
        });
    }
};
