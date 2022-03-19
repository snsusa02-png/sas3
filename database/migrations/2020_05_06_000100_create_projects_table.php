<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->string('name',120);
            $table->string('descript',300)->nullable();

            $table->biginteger('initorgid')->unsigned()->nullable()->index()->comment('Заказчик');
		$table->foreign('initorgid')->references('id')->on('orgs');

            $table->bigInteger('categoryid')->nullable()->unsigned()->comment('категория по proj_categories');
		$table->foreign('categoryid')->references('id')->on('proj_categories');

            $table->string('leadusername',90)->nullable();	//временно
            $table->bigInteger('leaduserid')->nullable()->unsigned()->comment('id пользователя - руководителя проекта');
		$table->foreign('leaduserid')->references('id')->on('users');

            $table->dateTime('plnbegdt')->nullable()->comment('Плановое начало работы')->useCurrent=true;
            $table->dateTime('plnenddt')->nullable()->comment('Плановое окончание работы');

            $table->dateTime('fctbegdt')->nullable()->comment('Плановое начало работы')->useCurrent=true;
            $table->dateTime('fctenddt')->nullable()->comment('Плановое окончание работы');

            $table->boolean('active')->default(true);

            $table->bigInteger('statusid')->nullable()->unsigned()->comment('статус по proj_statuses');
		$table->foreign('statusid')->references('id')->on('proj_statuses');


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
        Schema::dropIfExists('projects');
    }
}
