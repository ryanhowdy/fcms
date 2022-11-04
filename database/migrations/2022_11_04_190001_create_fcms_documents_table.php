<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmsDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcms_documents', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 50);
            $table->text('description');
            $table->string('mime', 50)->default('application/download');
            $table->integer('user')->index('fcms_documents_ibfk_1');
            $table->timestamp('date')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fcms_documents');
    }
}
