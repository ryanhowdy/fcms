<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsGalleryPhotosTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_gallery_photos_tags', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user')->default(0)->index('tag_user_ind');
            $table->integer('photo')->default(0)->index('tag_photo_ind');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_gallery_photos_tags');
    }
}
