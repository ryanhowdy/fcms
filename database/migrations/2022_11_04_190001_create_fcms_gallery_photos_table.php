<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsGalleryPhotosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_gallery_photos', function (Blueprint $table) {
            $table->integer('id', true);
            $table->timestamp('date')->useCurrent();
            $table->string('filename', 25)->default('noimage.gif');
            $table->integer('external_id')->nullable();
            $table->text('caption')->nullable();
            $table->integer('category')->default(0)->index('cat_ind');
            $table->integer('user')->default(0)->index('user_ind');
            $table->smallInteger('views')->default(0);
            $table->smallInteger('votes')->default(0);
            $table->float('rating', 10, 0)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_gallery_photos');
    }
}
