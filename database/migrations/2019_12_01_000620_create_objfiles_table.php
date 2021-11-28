<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateObjfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('objfiles', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('sysobjid')->unsigned()->comment('связь с sysobjs.id');
            $table->bigInteger('objid')->unsigned()->comment('связь c записью в конкретной таблице');

            $table->bigInteger('sysfiletype_id')->unsigned();
            $table->bigInteger('mimetypeid')->unsigned();
            $table->string('disk',16)->nullable()->default('local');
            $table->string('publicfilename',256);
            $table->string('systemfilename',256);
            $table->bigInteger('filesize')->unsigned();
            $table->bigInteger('doctypeid')->unsigned()->nullable()->comment('Тип документа по спр-ку doctypes');
            $table->bigInteger('docsubtypeid')->unsigned()->nullable()->comment('Подтип документа по спр-ку doctypes');
            $table->string('notes',256)->nullable();

            $table->Integer('ordr')->unsigned()->default(999)->comment('порядок вывода');

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');

            //FK
            $table->index('sysobjid','objid');
            $table->foreign('mimetypeid')->references('id')->on('mimetypes');
            $table->foreign('sysfiletype_id')->references('id')->on('sysfiletypes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('objfiles');
    }
}
