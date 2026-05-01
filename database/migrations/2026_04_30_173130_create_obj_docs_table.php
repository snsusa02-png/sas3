<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjDocsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obj_docs', function (Blueprint $table) {
            $table->id();
            $table->biginteger('sysobjid')->unsigned()->nullable();
	        $table->foreign('sysobjid')->references('id')->on('sysobjs')->onDelete('cascade')->onUpdate('cascade');

            $table->bigInteger('objid')->unsigned();

            $table->biginteger('doctypeid')->unsigned()->nullable()->comment('Тип документа');
		$table->foreign('doctypeid')->references('id')->on('doctypes');

            $table->date('docdate')->comment('Дата документа');
            $table->string('docseria',16)->nullable()->comment('Серия документа');
            $table->string('docnum',36)->comment('Номер документа');

//            $table->date('begdate')->default(date("Y-m-d"))->comment('Начало действия документа');
//            $table->date('enddate')->nullable->comment('Окончание действия документа');
            $table->date('begdate')->default(date("Y-m-d"))->comment('Начало действия документа');
            $table->date('enddate')->nullable()->comment('Окончание действия документа');


            $table->string('notes',160)->nullable()->comment('Примечания');

            $table->boolean('active')->default(1)->comment('1-активно, 0-черновик');

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)->comment('UserID, изменившего запись');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('obj_docs');
    }
}
