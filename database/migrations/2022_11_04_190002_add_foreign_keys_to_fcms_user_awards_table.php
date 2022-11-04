<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToFcmsUserAwardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcms_user_awards', function (Blueprint $table) {
            $table->foreign(['user'], 'fcms_user_awards_ibfk_1')->references(['id'])->on('fcms_users')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fcms_user_awards', function (Blueprint $table) {
            $table->dropForeign('fcms_user_awards_ibfk_1');
        });
    }
}
