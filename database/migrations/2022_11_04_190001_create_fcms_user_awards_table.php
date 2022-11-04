<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsUserAwardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_user_awards', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user')->default(0)->index('user');
            $table->string('award', 100);
            $table->integer('month');
            $table->dateTime('date')->nullable();
            $table->integer('item_id')->nullable();
            $table->smallInteger('count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_user_awards');
    }
}
