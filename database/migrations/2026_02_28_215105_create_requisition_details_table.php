<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('requisition_details', function (Blueprint $table) {
            $table->id();
            $table->integer('requisition_id')->index('requisition_id_details');
            $table->integer('product_id')->index('product_id_details');
            $table->integer('product_variant_id')->nullable()->index('variant_id_details');
            $table->double('quantity');
            $table->integer('unit_id')->index('unit_id_details');
            $table->timestamps(6);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requisition_details');
    }
};
