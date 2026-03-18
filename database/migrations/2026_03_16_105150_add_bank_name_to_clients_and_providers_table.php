<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBankNameToClientsAndProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('bank_account');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('bank_account');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('bank_name');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('bank_name');
        });
    }
}
