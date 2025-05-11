<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFuelcardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fuelcards', function (Blueprint $table) {
            $table->id();

            $table->string('num',16)->comment('Номер карты');
            $table->string('name',60)->nullable()->comment('Название/тип карты');

            $table->bigInteger('suporgid')->unsigned()->comment('Компания-поставщик топлива')->index('orgid');
		$table->foreign('suporgid')->references('id')->on('orgs');

            $table->bigInteger('orgid')->unsigned()->comment('Компания-владелец карты')->index('orgid');
		$table->foreign('orgid')->references('id')->on('orgs');

            $table->bigInteger('ref_machineid')->unsigned()->nullable();
		$table->foreign('ref_machineid')->references('id')->on('machines');

            $table->string('notes',300)->nullable()->comment('Примечание');

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
        Schema::dropIfExists('fuelcards');
    }
}
