<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToFcmsGalleryPhotosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcms_gallery_photos', function (Blueprint $table) {
            $table->foreign(['user'], 'fcms_gallery_photos_ibfk_1')->references(['id'])->on('fcms_users')->onUpdate('NO ACTION')->onDelete('CASCADE');
            $table->foreign(['category'], 'fcms_gallery_photos_ibfk_2')->references(['id'])->on('fcms_category')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fcms_gallery_photos', function (Blueprint $table) {
            $table->dropForeign('fcms_gallery_photos_ibfk_1');
            $table->dropForeign('fcms_gallery_photos_ibfk_2');
        });
    }
}
