<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsNewsCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_news_comments', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('news')->default(0)->index('photo_ind');
            $table->text('comment');
            $table->timestamp('date')->useCurrent();
            $table->integer('user')->default(0)->index('user_ind');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_news_comments');
    }
}
