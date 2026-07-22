<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovalLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('approval_logs')) {
            Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_id');
            $table->integer('user_id'); // matches users.id (signed int)
            $table->integer('level');
            $table->enum('action', ['approved', 'rejected'])->default('approved');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('approval_id')->references('id')->on('approvals')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approval_logs');
    }
}
