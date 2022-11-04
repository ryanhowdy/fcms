<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_user_settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user')->index('user_ind');
            $table->string('theme', 25)->default('default');
            $table->set('boardsort', ['ASC', 'DESC'])->default('ASC');
            $table->set('displayname', ['1', '2', '3'])->default('1');
            $table->set('frontpage', ['1', '2'])->default('1');
            $table->set('timezone', ['-12 hours', '-11 hours', '-10 hours', '-9 hours', '-8 hours', '-7 hours', '-6 hours', '-5 hours', '-4 hours', '-3 hours -30 minutes', '-3 hours', '-2 hours', '-1 hours', '-0 hours', '+1 hours', '+2 hours', '+3 hours', '+3 hours +30 minutes', '+4 hours', '+4 hours +30 minutes', '+5 hours', '+5 hours +30 minutes', '+6 hours', '+7 hours', '+8 hours', '+9 hours', '+9 hours +30 minutes', '+10 hours', '+11 hours', '+12 hours'])->default('-5 hours');
            $table->boolean('dst')->default(false);
            $table->boolean('email_updates')->default(false);
            $table->set('uploader', ['plupload', 'java', 'basic'])->default('plupload');
            $table->boolean('advanced_tagging')->default(true);
            $table->string('language', 6)->default('en_US');
            $table->integer('fs_user_id')->nullable();
            $table->char('fs_access_token', 50)->nullable();
            $table->string('blogger')->nullable();
            $table->string('tumblr')->nullable();
            $table->string('wordpress')->nullable();
            $table->string('posterous')->nullable();
            $table->string('fb_access_token')->nullable();
            $table->string('google_session_token')->nullable();
            $table->string('instagram_access_token')->nullable();
            $table->boolean('instagram_auto_upload')->nullable()->default(false);
            $table->string('picasa_session_token')->nullable();
            $table->string('fb_user_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_user_settings');
    }
}
