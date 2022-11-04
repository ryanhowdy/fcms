<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsRelationshipTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_relationship', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user')->index('user_ind');
            $table->string('relationship', 4);
            $table->integer('rel_user')->index('rel_user');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_relationship');
    }
}
