<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquiprqstStaffsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('equiprqst_staffs', function (Blueprint $table) {
            $table->id();
            $table->biginteger('rqstid')->unsigned()->index();
	        $table->foreign('rqstid')->references('id')->on('equiprqsts')->onDelete('cascade')->onUpdate('cascade');

	//            $table->integer('ordr')->default(1);
            $table->string('rolecode',16)->nullable()->comment('Код роли');
            $table->string('rolename',120)->nullable()->comment('Наименование роли');

            $table->biginteger('orgid')->unsigned()->nullable()->index()->comment('организация');
		$table->foreign('orgid')->references('id')->on('orgs');

            $table->biginteger('staffid')->unsigned()->nullable()->index()->comment('сотрудник');
		$table->foreign('staffid')->references('id')->on('orgstaff');

            $table->biginteger('userid')->unsigned()->nullable()->index()->comment('пользователь');
		$table->foreign('userid')->references('id')->on('users');

            $table->string('notes',200)->nullable()->comment('Примечание');

            $table->boolean('active')->default(true);

            $table->datetime('read_at')->nullable()->comment('Дата-время ознакомления');


            $table->boolean('signed')->nullable()->comment('null - решение не принято; 0-отказ, 1-подтверждение');
            $table->string('dcsn_descript',120)->nullable()->comment('Пояснение решения');
            $table->datetime('dcsn_at')->nullable()->comment('Дата-время принятия решения');

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
        Schema::dropIfExists('equiprqst_staffs');
    }
}
