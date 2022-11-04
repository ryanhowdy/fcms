<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsAddressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_address', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user')->default(0)->index('user_ind');
            $table->char('country', 2)->nullable();
            $table->string('address', 50)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('zip', 10)->nullable();
            $table->string('home', 20)->nullable();
            $table->string('work', 20)->nullable();
            $table->string('cell', 20)->nullable();
            $table->integer('created_id')->default(0)->index('create_ind');
            $table->dateTime('created');
            $table->integer('updated_id')->default(0)->index('update_ind');
            $table->timestamp('updated')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_address');
    }
}
