<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsInvitationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_invitation', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('event_id')->default(0)->index('event_id');
            $table->integer('user')->default(0);
            $table->string('email', 50)->nullable();
            $table->dateTime('created');
            $table->dateTime('updated')->nullable();
            $table->boolean('attending')->nullable();
            $table->char('code', 13)->nullable();
            $table->text('response')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_invitation');
    }
}
