<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoutePointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_points', function (Blueprint $table) {
            $table->id();
            $table->biginteger('src_placeid')->unsigned()->nullable()->comment('ID места начала маршрута');
            $table->string('src_placename',60)->nullable()->comment('Название(адрес) места погрузки');
            $table->biginteger('tgt_placeid')->unsigned()->nullable()->comment('ID места окончания маршрута');
            $table->string('tgt_placename',60)->nullable()->comment('Название(адрес) места выгрузки');

            $table->date('begdate')->default(date("Y-m-d"))->comment('Начало действия оценки');
            $table->date('enddate')->nullable()->comment('Окончание действия оценки');

	    $table->decimal('points',6,3)->comment('Кол-во баллов за поездку по данному маршруту');

            $table->boolean('active')->default(1)->comment('0-черновик; 1-используется в расчетах');

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
        Schema::dropIfExists('route_points');
    }
}
