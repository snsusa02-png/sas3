<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuildobjStaffsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buildobj_staffs', function (Blueprint $table) {
            $table->id();
            $table->biginteger('buildobjid')->unsigned()->nullable()->index();

            $table->biginteger('roleid')->unsigned()->nullable()->comment('Id роли сотрудника');
            $table->string('rolename',160);
            $table->biginteger('orgid')->unsigned()->nullable()->index()->comment('Id организации (orgs)');
            $table->biginteger('staffid')->unsigned()->nullable()->index()->comment('Id сотрудника (orgstaff)');
            $table->string('staffname',90)->nullable()->comment('временно(?) ФИО сотрудника');
            $table->string('reason',160)->nullable()->comment('Основание: приказ, доверенность');
            $table->string('descript',300)->nullable();

            $table->integer('ordr')->unsigned()->nullable()->default(999)->comment('примерный порядок');

            $table->biginteger('qcheck_resporgid')->unsigned()->nullable()->index()->comment('Id организации чьи косяки необходимо довести до этого сотрудника. NULL-от любой');

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
        Schema::dropIfExists('buildobj_staffs');
    }
}
