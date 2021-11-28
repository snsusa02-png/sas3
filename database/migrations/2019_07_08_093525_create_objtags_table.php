<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateObjtagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('objtags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sysobjid')->unsigned()->index('sysobjid')
		->comment('для какого типа объекта (sysobjs.id)');
            $table->bigInteger('objid')->unsigned()->index('objid')
		->nullable()->comment('для какого объекта');
            $table->string('type',30)->comment('Тип')->index('type');
            $table->string('val',220)->comment('Значение');
            $table->string('tag',255)->comment('Тэг=Тип:Значение')->index('tag');

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
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
        Schema::dropIfExists('objtags');
    }
}
