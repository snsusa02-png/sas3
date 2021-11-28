<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSysobjRoletypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	//Перечень ролей (пользователей) применимых для системного объекта
        Schema::create('sysobj_roletypes', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('sysobjid')->unsigned()->comment('ID системного объекта');
	        $table->foreign('sysobjid')->references('id')->on('sysobjs');

            $table->bigInteger('objid')->unsigned()->nullable()->comment('ID записи объекта. Null - для всех');

            $table->bigInteger('tgt_sysobjid')->unsigned()->nullable()->comment('ID системного объекта - для кого предназначено');

            $table->bigInteger('roletypeid')->unsigned()->comment('ID типа роли');
	        $table->foreign('roletypeid')->references('id')->on('roletypes');

            $table->Integer('ordr')->unsigned()->default(999)->comment('порядок вывода');

            $table->boolean('active')->default(1);


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
        Schema::dropIfExists('sysobj_roletypes');
    }
}
