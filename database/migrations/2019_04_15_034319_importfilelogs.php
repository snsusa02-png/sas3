<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class importfilelogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('importfilelogs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamp('write_at')->nullable()->useCurrent=true;
            $table->bigInteger('importfile_id')->unsigned();
            $table->tinyInteger('errlvl')->nullable()->comment('1- fatalerror, 2 - error ,3 info ,4 warning, 5 debug, 6 trace');
            $table->bigInteger('file_strpos')->nullable();
            $table->bigInteger('sysobjid')->nullable()->unsigned();
            $table->bigInteger('ref_id')->nullable()->unsigned();
            $table->string('info',256);
            //FK
            $table->index('importfile_id');
            $table->foreign('importfile_id')->references('id')->on('importfiles');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('importfilelogs');
    }
}
