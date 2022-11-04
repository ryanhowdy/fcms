<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsVideoCommentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_video_comment', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('video_id')->index('video_id');
            $table->text('comment');
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
        Schema::dropIfExists('fcms_video_comment');
    }
}
