<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsAlertsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_alerts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('alert', 50)->default('0')->index('alert_ind');
            $table->integer('user')->default(0)->index('user_ind');
            $table->boolean('hide')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_alerts');
    }
}
