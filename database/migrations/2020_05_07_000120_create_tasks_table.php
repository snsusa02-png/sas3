<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('parid')->nullable()->unsigned()->comment('id родительской задачи')->index();
		$table->foreign('parid')->references('id')->on('tasks');

            $table->string('name',160)->nullable()->comment('Название / суть задачи');
            $table->string('descript',360)->nullable()->comment('Описание задачи');

            $table->bigInteger('categoryid')->nullable()->unsigned()->comment('id категории')->index();
		$table->foreign('categoryid')->references('id')->on('proj_categories');

            $table->string('category',90)->nullable()->comment('Категория задачи - произвольная');

            $table->bigInteger('inituserid')->nullable()->unsigned()->comment('id инициатора задачи')->index();
		$table->foreign('inituserid')->references('id')->on('users');

            $table->tinyinteger('public_lvl')->unsigned()->nullable()->default(0)->comment('0-личная, 1-видно дата/время, 2-видно всем');
            $table->tinyinteger('priority')->unsigned()->nullable()->default(0)->comment('больше число - больше приоритет');

            $table->decimal('plncostsum',12,2)->nullable()->comment('Планируемые затраты, руб');

            $table->boolean('need_check')->default(false)->comment('1 - требуется проверка результата инициатором');
            $table->bigInteger('checkuserid')->nullable()->unsigned()->comment('id проеряющего исполнение задачи');
		$table->foreign('checkuserid')->references('id')->on('users');

            $table->dateTime('plnbegdt')->nullable()->comment('Плановое начало работы')->useCurrent=true;
            $table->Integer('day_duration')->nullable()->default(1)->unsigned()->comment('Продолжительность задачи в днях');
            $table->dateTime('plnenddt')->nullable()->comment('Плановое окончание работы');

            $table->string('place',160)->nullable()->comment('Место решения задачи. Если необходимо');

            $table->dateTime('fctbegdt')->nullable()->comment('Плановое начало работы');
            $table->dateTime('fctenddt')->nullable()->comment('Плановое окончание работы');
            $table->tinyInteger('progress')->unsigned()->nullable()->default(0)->comment('Прогресс исполнения в %: 0 - 100');

            $table->bigInteger('exeuserid')->nullable()->unsigned()->comment('id текущего исполнителя задачи');
		$table->foreign('exeuserid')->references('id')->on('users');

            $table->boolean('active')->default(true);

            $table->bigInteger('statusid')->nullable()->unsigned()->comment('статус по task_statuses');
		$table->foreign('statusid')->references('id')->on('task_statuses');

            $table->bigInteger('reptypeid')->nullable()->unsigned()->comment('статус по task_reptypes');
		$table->foreign('reptypeid')->references('id')->on('task_reptypes');

            $table->bigInteger('notifytypeid')->nullable()->unsigned()->comment('вариант напоминания');

            $table->bigInteger('srcsysobjid')->nullable()->unsigned()->comment('id типа объекта - источника задачи');
		$table->foreign('srcsysobjid')->references('id')->on('sysobjs');
            $table->bigInteger('srcobjid')->nullable()->unsigned()->comment('id объекта в таблице, определяемой srcsysobjid');
            $table->string('srcobjinfo',160)->nullable()->comment('Информация о связанном объекте - для отображения в списках');

            $table->integer('sortorder')->unsigned()->nullable()->default(0)->comment('');


//            $table->string('transmitter');
//            $table->string('assigned');
//            $table->string('risk');

            $table->biginteger('projid')->unsigned()->nullable();
            $table->foreign('projid')->references('id')->on('projects');

            $table->biginteger('milestoneid')->unsigned()->nullable();
            $table->foreign('milestoneid')->references('id')->on('proj_milestones');

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
        Schema::dropIfExists('tasks');
    }
}
