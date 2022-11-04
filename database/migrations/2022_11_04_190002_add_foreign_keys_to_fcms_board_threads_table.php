<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToFcmsBoardThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcms_board_threads', function (Blueprint $table) {
            $table->foreign(['started_by'], 'fcms_threads_ibfk_1')->references(['id'])->on('fcms_users')->onUpdate('NO ACTION')->onDelete('CASCADE');
            $table->foreign(['updated_by'], 'fcms_threads_ibfk_2')->references(['id'])->on('fcms_users')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fcms_board_threads', function (Blueprint $table) {
            $table->dropForeign('fcms_threads_ibfk_1');
            $table->dropForeign('fcms_threads_ibfk_2');
        });
    }
}
