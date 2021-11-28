<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjReadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obj_readers', function (Blueprint $table) {
            $table->id();

		$table->unsignedBigInteger('sysobjid');
		      $table->foreign('sysobjid')->references('id')->on('sysobjs');
	        $table->unsignedBigInteger('objid');

	        $table->unsignedBigInteger('userid')->comment('читатель');
			$table->foreign('userid')
			        ->references('id')->on('users')
			        ->onDelete('cascade');

	        $table->unsignedBigInteger('roletypeid')->nullable()->comment('тип роли пользователя');

	        $table->boolean('mustread')->default(0);

	        $table->datetime('firstread_at')->nullable();
	        $table->datetime('lastread_at')->nullable();
		$table->integer('read_cnt')->default(0);

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
        Schema::dropIfExists('obj_readers');
    }
}
