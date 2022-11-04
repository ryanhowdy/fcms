<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToFcmsBoardPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcms_board_posts', function (Blueprint $table) {
            $table->foreign(['thread'], 'fcms_posts_ibfk_1')->references(['id'])->on('fcms_board_threads')->onUpdate('NO ACTION')->onDelete('CASCADE');
            $table->foreign(['user'], 'fcms_posts_ibfk_2')->references(['id'])->on('fcms_users')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fcms_board_posts', function (Blueprint $table) {
            $table->dropForeign('fcms_posts_ibfk_1');
            $table->dropForeign('fcms_posts_ibfk_2');
        });
    }
}
