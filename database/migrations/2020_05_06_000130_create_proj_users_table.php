<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proj_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('projid')->nullable()->unsigned()->comment('id проекта');
		$table->foreign('projid')->references('id')->on('projects')->onDelete('cascade');

            $table->bigInteger('userid')->nullable()->unsigned()->comment('id пользователя');
		$table->foreign('userid')->references('id')->on('users');

            $table->bigInteger('roletypeid')->nullable()->unsigned()->comment('id типа роли ()');
            $table->string('rolename',120)->nullable()->comment('название роли пользователя в проекте');

            $table->dateTime('begdt')->nullable()->comment('Начало работы по проекту')->useCurrent=true;
            $table->dateTime('enddt')->nullable()->comment('Окончание работы по проекту');

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
        Schema::dropIfExists('proj_users');
    }
}
