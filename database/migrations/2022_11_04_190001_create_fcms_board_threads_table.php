<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsBoardThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_board_threads', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('subject', 50)->default('Subject');
            $table->integer('started_by')->default(0)->index('start_ind');
            $table->timestamp('updated')->useCurrent();
            $table->integer('updated_by')->default(0)->index('up_ind');
            $table->smallInteger('views')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_board_threads');
    }
}
