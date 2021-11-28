<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateObjextidsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('objextids', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('sysobjid')->unsigned()->index('sysobjid');
            $table->bigInteger('objid')->unsigned()->index('objid');

            $table->bigInteger('extsysid')->unsigned()->index('extsysid')
		->comment('по ExtSystems.ID');

            $table->string('extid',50)->comment('Код объекта во внешней системе');

//            $table->string('objname',36)->nullable()->comment('Название объекта в локальной системе');

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');


            $table->foreign('extsysid')->references('id')->on('extsystems');
//            $table->foreign('orgid')->references('id')->on('orgs')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('objextids');
    }
}
