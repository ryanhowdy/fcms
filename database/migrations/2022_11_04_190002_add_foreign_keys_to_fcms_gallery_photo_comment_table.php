<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToFcmsGalleryPhotoCommentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcms_gallery_photo_comment', function (Blueprint $table) {
            $table->foreign(['user'], 'fcms_gallery_photo_comment_ibfk_1')->references(['id'])->on('fcms_users')->onUpdate('NO ACTION')->onDelete('CASCADE');
            $table->foreign(['photo'], 'fcms_gallery_photo_comment_ibfk_2')->references(['id'])->on('fcms_gallery_photos')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fcms_gallery_photo_comment', function (Blueprint $table) {
            $table->dropForeign('fcms_gallery_photo_comment_ibfk_1');
            $table->dropForeign('fcms_gallery_photo_comment_ibfk_2');
        });
    }
}
