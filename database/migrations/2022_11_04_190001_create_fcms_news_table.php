<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsNewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_news', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('title', 50)->default('');
            $table->text('news');
            $table->integer('user')->default(0)->index('userindx');
            $table->dateTime('created');
            $table->dateTime('updated');
            $table->string('external_type', 20)->nullable();
            $table->string('external_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_news');
    }
}
