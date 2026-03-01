<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddBankAccountToTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $translations = [
            ['locale' => 'en', 'key' => 'Bank_Account', 'value' => 'Bank Account'],
            ['locale' => 'fr', 'key' => 'Bank_Account', 'value' => 'Compte Bancaire'],
            ['locale' => 'ar', 'key' => 'Bank_Account', 'value' => 'حساب بنكي'],
            ['locale' => 'es', 'key' => 'Bank_Account', 'value' => 'Cuenta bancaria'],
            ['locale' => 'de', 'key' => 'Bank_Account', 'value' => 'Bankkonto'],
            ['locale' => 'it', 'key' => 'Bank_Account', 'value' => 'Conto bancario'],
            ['locale' => 'ru', 'key' => 'Bank_Account', 'value' => 'Банковский счет'],
            ['locale' => 'tr', 'key' => 'Bank_Account', 'value' => 'Banka hesabı'],
        ];

        foreach ($translations as $translation) {
            DB::table('translations')->updateOrInsert(
                ['locale' => $translation['locale'], 'key' => $translation['key']],
                ['value' => $translation['value']]
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('translations')->where('key', 'Bank_Account')->delete();
    }
}
