<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsCalendarTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_calendar', function (Blueprint $table) {
            $table->integer('id', true);
            $table->date('date');
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->dateTime('date_added')->nullable();
            $table->string('title', 50)->default('MyDate');
            $table->text('desc')->nullable();
            $table->integer('created_by')->default(0)->index('by_ind');
            $table->integer('category')->default(0);
            $table->string('repeat', 20)->nullable();
            $table->boolean('private')->default(false);
            $table->boolean('invite')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_calendar');
    }
}
