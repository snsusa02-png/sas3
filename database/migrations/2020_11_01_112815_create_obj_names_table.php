<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjNamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obj_names', function (Blueprint $table) {
            $table->id();

		$table->unsignedBigInteger('sysobjid');
		      $table->foreign('sysobjid')->references('id')->on('sysobjs');
	        $table->unsignedBigInteger('objid');

	        $table->string('name',300)->comment('Название/альтернативное');

	        $table->boolean('active')->default(1);

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');


            $table->index(['sysobjid','objid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('obj_names');
    }
}
