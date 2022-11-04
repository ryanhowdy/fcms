<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_notification', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user')->default(0)->index('user');
            $table->integer('created_id')->default(0);
            $table->string('notification', 50)->nullable();
            $table->string('data', 50);
            $table->boolean('read')->default(false);
            $table->dateTime('created');
            $table->dateTime('updated')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_notification');
    }
}
