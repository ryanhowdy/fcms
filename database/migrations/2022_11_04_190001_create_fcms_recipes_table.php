<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsRecipesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_recipes', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 50)->default('My Recipe');
            $table->string('thumbnail')->default('no_recipe.jpg');
            $table->integer('category');
            $table->text('ingredients');
            $table->text('directions');
            $table->integer('user')->index('fcms_recipes_ibfk_1');
            $table->timestamp('date')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_recipes');
    }
}
