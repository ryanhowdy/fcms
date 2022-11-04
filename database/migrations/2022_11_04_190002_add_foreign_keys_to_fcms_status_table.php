<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToFcmsStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcms_status', function (Blueprint $table) {
            $table->foreign(['user'], 'fcms_status_ibfk_1')->references(['id'])->on('fcms_users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fcms_status', function (Blueprint $table) {
            $table->dropForeign('fcms_status_ibfk_1');
        });
    }
}
