<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsVideoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_video', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('source_id');
            $table->string('title')->default('untitled');
            $table->string('description')->nullable();
            $table->integer('duration')->nullable();
            $table->string('source', 50)->nullable();
            $table->integer('height')->default(420);
            $table->integer('width')->default(780);
            $table->boolean('active')->default(true);
            $table->dateTime('created');
            $table->integer('created_id');
            $table->dateTime('updated');
            $table->integer('updated_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_video');
    }
}
