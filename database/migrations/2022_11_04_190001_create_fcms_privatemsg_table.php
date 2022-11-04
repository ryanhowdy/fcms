<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsPrivatemsgTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_privatemsg', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('to')->index('to_ind');
            $table->integer('from')->index('from_ind');
            $table->dateTime('date')->nullable();
            $table->string('title', 50)->default('PM Title');
            $table->text('msg')->nullable();
            $table->boolean('read')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_privatemsg');
    }
}
