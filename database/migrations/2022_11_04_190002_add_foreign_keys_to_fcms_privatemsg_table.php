<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToFcmsPrivatemsgTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcms_privatemsg', function (Blueprint $table) {
            $table->foreign(['to'], 'fcms_privatemsg_ibfk_1')->references(['id'])->on('fcms_users')->onUpdate('NO ACTION')->onDelete('CASCADE');
            $table->foreign(['from'], 'fcms_privatemsg_ibfk_2')->references(['id'])->on('fcms_users')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fcms_privatemsg', function (Blueprint $table) {
            $table->dropForeign('fcms_privatemsg_ibfk_1');
            $table->dropForeign('fcms_privatemsg_ibfk_2');
        });
    }
}
