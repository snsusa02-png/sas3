<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMachinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('name',120);
            $table->biginteger('mchntypeid')->unsigned()->nullable()->index();
            $table->string('regnum',20)->nullable()->comment('Гос. регистрационный номер');
            $table->string('descript',300)->nullable();
            $table->biginteger('orgid')->unsigned()->nullable()->index()->comment('Текущий владелец');
            $table->string('photourl', 90)->nullable();
            $table->string('opercodes', 160)->nullable()->comment('список кодов операций/функций, которые поддерживает данная техника, например: /CARGO/LIFT/');
            $table->tinyinteger('fueltypeid')->unsigned()->nullable()->comment('Тип топлива: 1-ДТ, 2-АИ92, 3-АИ95, 4-АИ98');
            $table->decimal('fuelper1hour',5,1)->nullable()->comment('расход литров в час');
            $table->decimal('fuelper100km',6,1)->nullable()->comment('расход литров на 100 км');

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
        Schema::dropIfExists('machines');
    }
}
