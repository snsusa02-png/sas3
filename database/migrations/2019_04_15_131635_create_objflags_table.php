<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateObjflagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('objflags', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('sysobjid')->unsigned()->comment('для какого типа объекта (sysobjs.id)');
            $table->bigInteger('objid')->unsigned()->comment('для какого объекта');
            $table->bigInteger('flagtypeid')->unsigned()->comment('какой флаг (flagtypes.id)');

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('objflags');
    }
}
