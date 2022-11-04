<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_category', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 50);
            $table->string('type', 20);
            $table->integer('user')->index('user_ind');
            $table->timestamp('date')->useCurrent();
            $table->string('color', 20)->nullable();
            $table->string('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_category');
    }
}
