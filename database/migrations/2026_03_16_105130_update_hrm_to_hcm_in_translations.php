<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateHrmToHcmInTranslations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('translations')->where('key', 'hrm')->update(['value' => 'HCM']);
        
        // Also ensure HCM key exists if hrm was just a legacy name
        DB::table('translations')->updateOrInsert(
            ['locale' => 'en', 'key' => 'HCM'],
            ['value' => 'HCM', 'is_default' => 1]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('translations')->where('key', 'hrm')->update(['value' => 'HRM']);
    }
}
