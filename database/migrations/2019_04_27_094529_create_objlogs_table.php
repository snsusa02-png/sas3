<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateObjlogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('objlogs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->timestamp('write_at')->nullable()->useCurrent=true;
            $table->bigInteger('write_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');

            $table->bigInteger('sysobjid')->unsigned()->comment('связь с sysobjs.id');
            $table->bigInteger('objid')->unsigned()->comment('связь c записью в конкретной таблице');
            $table->bigInteger('objpos')->nullable()->comment('Позиция внутри записи objid');
            $table->tinyInteger('errlvl')->nullable()
                ->comment('уровень сообщения: 1- fatalerror, 2 - error, 3 info, 4 warning, 5 debug, 6 trace');
            $table->string('info',256);

            //FK
            $table->index('sysobjid','objid');
            //$table->foreign('importfile_id')->references('id')->on('importfiles');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('objlogs');
    }
}
