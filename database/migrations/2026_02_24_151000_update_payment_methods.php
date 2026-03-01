<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Rename 'Credit Card' to 'Card'
        DB::table('payment_methods')
            ->where('id', 1)
            ->update(['name' => 'Card']);

        // Add 'Mobile Money (MoMo)' if it doesn't exist
        DB::table('payment_methods')->updateOrInsert(
            ['name' => 'Mobile Money (MoMo)'],
            ['updated_at' => now()]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert 'Card' to 'Credit Card'
        DB::table('payment_methods')
            ->where('id', 1)
            ->update(['name' => 'Credit Card']);

        // Remove 'Mobile Money (MoMo)'
        DB::table('payment_methods')
            ->where('name', 'Mobile Money (MoMo)')
            ->delete();
    }
};
