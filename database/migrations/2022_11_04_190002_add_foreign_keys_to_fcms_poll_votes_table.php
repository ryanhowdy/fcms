<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToFcmsPollVotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcms_poll_votes', function (Blueprint $table) {
            $table->foreign(['user'], 'fcms_poll_votes_ibfk_1')->references(['id'])->on('fcms_users')->onUpdate('NO ACTION')->onDelete('CASCADE');
            $table->foreign(['option'], 'fcms_poll_votes_ibfk_2')->references(['id'])->on('fcms_poll_options')->onUpdate('NO ACTION')->onDelete('CASCADE');
            $table->foreign(['poll_id'], 'fcms_poll_votes_ibfk_3')->references(['id'])->on('fcms_polls')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fcms_poll_votes', function (Blueprint $table) {
            $table->dropForeign('fcms_poll_votes_ibfk_1');
            $table->dropForeign('fcms_poll_votes_ibfk_2');
            $table->dropForeign('fcms_poll_votes_ibfk_3');
        });
    }
}
