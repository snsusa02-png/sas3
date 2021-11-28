<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGrpitemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grpitems', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('grpid')->unsigned()->index('grpid')->comment('Группа по groups.ID');
            $table->bigInteger('sysobjid')->unsigned()->comment('по sysobjs.ID');
            $table->bigInteger('objid')->unsigned()->comment('по objs.ID');
            $table->string('objname',36)->nullable()->comment('имя связанного объекта');

            $table->boolean('active')->default(true);

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');
	
	
		$table->index(['sysobjid','objid']);
		$table->foreign('grpid')->references('id')->on('groups')
			->onDelete('cascade')->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('grpitems');
    }
}
