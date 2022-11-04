<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsPollCommentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_poll_comment', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('poll_id');
            $table->text('comment');
            $table->timestamp('created')->useCurrent();
            $table->integer('created_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_poll_comment');
    }
}
