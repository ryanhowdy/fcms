<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_users', function (Blueprint $table) {
            $table->integer('id', true);
            $table->boolean('access')->default(false);
            $table->dateTime('activity')->nullable();
            $table->dateTime('joindate')->nullable();
            $table->string('fname', 25)->default('fname');
            $table->string('mname', 25)->nullable();
            $table->string('lname', 25)->default('lname');
            $table->string('maiden', 25)->nullable();
            $table->char('sex', 1)->default('M');
            $table->string('email', 50)->default('me@mail.com');
            $table->char('dob_year', 4)->nullable();
            $table->char('dob_month', 2)->nullable();
            $table->char('dob_day', 2)->nullable();
            $table->char('dod_year', 4)->nullable();
            $table->char('dod_month', 2)->nullable();
            $table->char('dod_day', 2)->nullable();
            $table->string('username', 25)->default('0')->unique('username');
            $table->string('password')->default('0');
            $table->string('phpass')->default('0');
            $table->string('token')->nullable();
            $table->string('avatar', 25)->default('no_avatar.jpg');
            $table->string('gravatar')->nullable();
            $table->string('bio', 200)->nullable();
            $table->char('activate_code', 13)->nullable();
            $table->boolean('activated')->default(false);
            $table->boolean('login_attempts')->default(false);
            $table->dateTime('locked')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_users');
    }
}
