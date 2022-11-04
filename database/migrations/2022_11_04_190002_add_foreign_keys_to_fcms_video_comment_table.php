<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToFcmsVideoCommentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcms_video_comment', function (Blueprint $table) {
            $table->foreign(['video_id'], 'fcms_video_comment_ibfk_1')->references(['id'])->on('fcms_video')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fcms_video_comment', function (Blueprint $table) {
            $table->dropForeign('fcms_video_comment_ibfk_1');
        });
    }
}
