<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDwBreaksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dw_breaks', function (Blueprint $table) {
            $table->id();

            $table->biginteger('dw_id')->unsigned()->nullable()->comment('Связка с отчетом о работе');
		$table->foreign('dw_id')->references('id')->on('driver_works');

            $table->biginteger('breaktypeid')->unsigned()->default(1)->comment('Тип простоя: 1-по уважительной причине, 2-ремонт');

            $table->dateTime('breakbegdt')->nullable()->comment('Время начала работы');
            $table->dateTime('breakenddt')->nullable()->comment('Время окончания работы');
            $table->decimal('breakhrs',4,1)->nullable()->comment('кол-во рабочих часов работника');

            $table->string('reason',160)->nullable()->comment('Описание причины простоя');

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
        Schema::dropIfExists('dw_breaks');
    }
}
