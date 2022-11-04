<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsGalleryExternalPhotoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_gallery_external_photo', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('source_id');
            $table->string('thumbnail');
            $table->string('medium');
            $table->string('full');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_gallery_external_photo');
    }
}
