<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSysobjAddresstypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sysobj_addresstypes', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('sysobjid')->unsigned()->comment('ID системного объекта');
	        $table->foreign('sysobjid')->references('id')->on('sysobjs');

            $table->bigInteger('objid')->unsigned()->nullable()->comment('ID записи объекта. Null - для всех');

       //     $table->bigInteger('tgt_sysobjid')->unsigned()->nullable()->comment('ID системного объекта - для кого предназначено');

            $table->bigInteger('addresstypeid')->unsigned()->comment('ID типа роли');
//	        $table->foreign('addresstypeid')->references('id')->on('addresstypes');

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
        Schema::dropIfExists('sysobj_addresstypes');
    }
}
