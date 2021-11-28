<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMchnOpertypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mchn_opertypes', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('machineid')->unsigned()->index('machineid');
		$table->foreign('machineid')->references('id')->on('machines');

            $table->string('name',60);
            $table->string('descript',160)->nullable();

            $table->decimal('hour_work_cost',10,2)->nullable()->comment('Стоимость 1 часа работ');
            $table->decimal('hour_fuel_cost',10,2)->nullable()->comment('Стоимость 1 часа на топливо');

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
        Schema::dropIfExists('mchn_opertypes');
    }
}
