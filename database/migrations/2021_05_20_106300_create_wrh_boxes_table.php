<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWrhBoxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wrh_boxes', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('wrhid')->unsigned()->comment('ID склада');
	        $table->foreign('wrhid')->references('id')->on('wrhs');

            $table->string('code',16)->comment('Код/Этикетка ячейки. Должен быть уникален в пределах склада');
            $table->string('name',160)->comment('Название');
            $table->string('descript',360)->nullable()->comment('Описание ячейки хранения');

            $table->bigInteger('buildobjid')->unsigned()->nullable()->comment('ID строительного объекта');
	        $table->foreign('buildobjid')->references('id')->on('buildobjs');

            $table->bigInteger('buildopertypeid')->unsigned()->nullable()->comment('ID вида работ');
	        $table->foreign('buildopertypeid')->references('id')->on('buildopertypes');

            $table->bigInteger('contractid')->unsigned()->nullable()->comment('ID договора с подрядчиком');
	        $table->foreign('contractid')->references('id')->on('contracts');

            $table->bigInteger('orgid')->unsigned()->nullable()->comment('ID организации-подрядчика');
	        $table->foreign('orgid')->references('id')->on('orgs');

 	   $table->biginteger('bdgtitmsumid')->unsigned()->nullable()->index()->comment('источник финансирования');
		$table->foreign('bdgtitmsumid')->references('id')->on('budget_itmsums');

            $table->boolean('active')->default(1);


            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');


	$table->index(['wrhid','code']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ri_wrhboxes');

        Schema::dropIfExists('wrh_boxes');
    }
}
