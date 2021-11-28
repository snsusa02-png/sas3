<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjmilestonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	//Дорожная карта / Вехи для объекта/проекта/работы
        Schema::create('objmilestones', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('sysobjid')->unsigned()->comment('id типа объекта');
		$table->foreign('sysobjid')->references('id')->on('sysobjs')->onDelete('cascade');

            $table->bigInteger('objid')->unsigned()->comment('id объекта в пределах sysobjid');

            $table->string('name',120);
            $table->integer('ordr')->default(10);
            $table->string('descript',300)->nullable();

            $table->dateTime('plnbegdt')->nullable()->comment('Плановое начало работы')->useCurrent=true;
            $table->dateTime('plnenddt')->nullable()->comment('Плановое окончание работы');

            $table->dateTime('fctbegdt')->nullable()->comment('Плановое начало работы')->useCurrent=true;
            $table->dateTime('fctenddt')->nullable()->comment('Плановое окончание работы');

	    //нужно разобраться ------------	
            $table->string('dependance',90)->nullable();
            $table->string('dependant',90)->nullable();

            $table->boolean('active')->default(true);

            $table->bigInteger('statusid')->nullable()->unsigned()->comment('статус по milestone_statuses');
		$table->foreign('statusid')->references('id')->on('milestone_statuses');

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('objmilestones');
    }
}
