<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('taskid')->nullable()->unsigned()->comment('id задачи');
		$table->foreign('taskid')->references('id')->on('tasks')->onDelete('cascade');

            $table->bigInteger('userid')->nullable()->unsigned()->comment('id пользователя');
		$table->foreign('userid')->references('id')->on('users');

            $table->bigInteger('roletypeid')->nullable()->unsigned()->comment('id типа роли ()');
            $table->string('rolename',120)->nullable()->comment('название роли пользователя в задаче');

            $table->dateTime('begdt')->nullable()->comment('Начало работы по задаче')->useCurrent=true;
            $table->dateTime('enddt')->nullable()->comment('Окончание работы по задаче');

            $table->dateTime('nxtrepdt')->nullable()->comment('Дать отчет до указанного времени');

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
        Schema::dropIfExists('task_users');
    }
}
