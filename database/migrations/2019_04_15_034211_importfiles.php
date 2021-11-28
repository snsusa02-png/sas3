<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Importfiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('importfiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sysfiletype_id')->unsigned();
            $table->bigInteger('mimetype_id')->unsigned();
            $table->string('clientfilename',256);
            $table->string('systemfilename',256);
            $table->bigInteger('filesize')->unsigned();
            $table->bigInteger('import_by')->unsigned();
            $table->timestamp('import_at')->nullable()->useCurrent=true;
            $table->string('importnotes',256)->nullable();
            $table->foreign('sysfiletype_id')->references('id')->on('sysfiletypes');
            $table->foreign('mimetype_id')->references('id')->on('mimetypes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('importfiles');
    }
}
