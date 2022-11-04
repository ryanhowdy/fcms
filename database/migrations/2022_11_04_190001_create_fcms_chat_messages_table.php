<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsChatMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_chat_messages', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('userID');
            $table->string('userName', 64);
            $table->integer('userRole');
            $table->integer('channel');
            $table->dateTime('dateTime');
            $table->binary('ip');
            $table->text('text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_chat_messages');
    }
}
