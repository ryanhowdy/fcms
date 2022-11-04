<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToFcmsPollOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcms_poll_options', function (Blueprint $table) {
            $table->foreign(['poll_id'], 'fcms_poll_options_ibfk_1')->references(['id'])->on('fcms_polls')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fcms_poll_options', function (Blueprint $table) {
            $table->dropForeign('fcms_poll_options_ibfk_1');
        });
    }
}
