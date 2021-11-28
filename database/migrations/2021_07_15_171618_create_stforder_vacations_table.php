<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStforderVacationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('stforder_vacations', function (Blueprint $table) {
            $table->id();

            $table->tinyinteger('vactypeid')->unsigned()->nullable()->comment('ID типа отпуска ()');
	    $table->string('vactype',24)->nullable()->comment('Тип отпуска - ежегодный/по беременности/по уходу за ребенком/без сохранения з/платы');

            $table->date('wrkbegdate')->nullable()->comment('за период работы (с)');
            $table->date('wrkenddate')->nullable()->comment('за период работы (по)');

            $table->integer('privacdays')->unsigned()->nullable()->comment('кол-во календарных дней основного отпуска');
            $table->tinyinteger('priholdays')->unsigned()->nullable()->comment('кол-во дней праздников, пришедшихся на основной отпуск');

            $table->date('pribegdate')->nullable()->comment('Дата начала основного отпуска');
            $table->date('prienddate')->nullable()->comment('Последний день осн. отпуска');

	    $table->string('secvactype',90)->nullable()->comment('Тип дополнительного отпуска');

            $table->integer('secvacdays')->unsigned()->nullable()->comment('кол-во календарных дней дополнительного отпуска');
            $table->tinyinteger('secholdays')->unsigned()->nullable()->comment('кол-во дней праздников, пришедшихся на дополнительный отпуск');

            $table->date('secbegdate')->nullable()->comment('Дата начала доп. отпуска');
            $table->date('secenddate')->nullable()->comment('Последний день доп. отпуска');

            $table->string('item_3',512)->nullable()->comment('Дополнительный пункт приказа');
        
            $table->biginteger('vacrqstid')->unsigned()->nullable()->comment('ID заявки на отпуск (из StfVacPlans)');
        
	    $table->tinyinteger('compensatedays')->unsigned()->nullable()->comment('Кол-во дней отпуска, замененных денежной компенсацией');


            $table->timestamp('created_at')->nullable()->useCurrent = true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
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
        Schema::dropIfExists('stforder_vacations');
    }
}
