<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsBoardPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_board_posts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->timestamp('date')->useCurrent();
            $table->integer('thread')->default(0)->index('thread_ind');
            $table->integer('user')->default(0)->index('user_ind');
            $table->text('post');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_board_posts');
    }
}
