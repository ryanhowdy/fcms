<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsChatOnlineTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_chat_online', function (Blueprint $table) {
            $table->integer('userID');
            $table->string('userName', 64);
            $table->integer('userRole');
            $table->integer('channel');
            $table->dateTime('dateTime');
            $table->binary('ip');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_chat_online');
    }
}
