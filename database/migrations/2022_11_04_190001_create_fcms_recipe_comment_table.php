<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsRecipeCommentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_recipe_comment', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('recipe')->index('recipe');
            $table->text('comment');
            $table->timestamp('date')->useCurrent();
            $table->integer('user');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_recipe_comment');
    }
}
