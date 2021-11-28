<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMotPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mot_prices', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('mot_id')->unsigned()->index('mot_id');
		$table->foreign('mot_id')->references('id')->on('mchn_opertypes');

            $table->dateTime('begdt')->comment('Начало действия расценки')->useCurrent=true;
            $table->dateTime('enddt')->comment('Окончания действия расценки')->nullable();

            $table->decimal('hour_work_cost',10,2)->nullable()->comment('Стоимость 1 часа работ');
            $table->decimal('hour_fuel_cost',10,2)->nullable()->comment('Стоимость 1 часа на топливо');

            $table->string('notes',160)->nullable();

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
        Schema::dropIfExists('mot_prices');
    }
}
