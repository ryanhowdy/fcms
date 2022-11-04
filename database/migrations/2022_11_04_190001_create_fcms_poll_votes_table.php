<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsPollVotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_poll_votes', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user')->default(0)->index('user_ind');
            $table->integer('option')->default(0)->index('option_ind');
            $table->integer('poll_id')->default(0)->index('poll_id_ind');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_poll_votes');
    }
}
