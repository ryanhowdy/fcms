<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToFcmsNewsCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcms_news_comments', function (Blueprint $table) {
            $table->foreign(['news'], 'fcms_news_comments_ibfk_1')->references(['id'])->on('fcms_news')->onUpdate('NO ACTION')->onDelete('CASCADE');
            $table->foreign(['user'], 'fcms_news_comments_ibfk_2')->references(['id'])->on('fcms_users')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fcms_news_comments', function (Blueprint $table) {
            $table->dropForeign('fcms_news_comments_ibfk_1');
            $table->dropForeign('fcms_news_comments_ibfk_2');
        });
    }
}
