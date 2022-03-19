<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_reports', function (Blueprint $table) {
		// отчет исполнителя по задаче (периодический для больших задач)

            $table->id();

            $table->bigInteger('taskid')->nullable()->unsigned()->comment('id задачи');
		$table->foreign('taskid')->references('id')->on('tasks')->onDelete('cascade');

            $table->bigInteger('userid')->nullable()->unsigned()->comment('id пользователя');
		$table->foreign('userid')->references('id')->on('users');

            $table->dateTime('wrkbegdt')->nullable()->comment('Отчетный период работы - начало');
            $table->dateTime('wrkenddt')->nullable()->comment('Отчетный период работы - окончание')->useCurrent=true;

            $table->string('report',300)->nullable()->comment('Отчет по исполнению задачи');

            $table->tinyInteger('progress')->unsigned()->nullable()->default(0)->comment('Прогресс исполнения в %: 0 - 100');

            $table->bigInteger('plnstatusid')->nullable()->unsigned()->comment('id рекомендованного статуса исполнения задачи');
		$table->foreign('plnstatusid')->references('id')->on('task_statuses');

            $table->boolean('active')->default(true);

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
        Schema::dropIfExists('task_reports');
    }
}
