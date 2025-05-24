<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStfPrizetypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stf_prizetypes', function (Blueprint $table) {

            $table->id();
            $table->biginteger('staffid')->unsigned()->nullable()->comment('Организация');
		$table->foreign('staffid')->references('id')->on('orgstaff');

            $table->date('begdate')->comment('начало действия');
            $table->date('enddate')->comment('конец действия')->nullable();

            $table->biginteger('prizetypeid')->unsigned()->comment('Тип премии');
		$table->foreign('prizetypeid')->references('id')->on('prizetypes');

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
        Schema::dropIfExists('stf_prizetypes');
    }
}
