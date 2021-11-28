<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriverWorksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_works', function (Blueprint $table) {
            $table->id();

            $table->biginteger('staffid')->unsigned()->nullable()->comment('Сотрудник');
		$table->foreign('staffid')->references('id')->on('orgstaff');

            $table->bigInteger('machineid')->unsigned()->comment('Автомобиль/Спецтехника по спр-ку machines');
		$table->foreign('machineid')->references('id')->on('machines');

            $table->date('wrkdate')->comment('Дата учета данных в табеле. (?) Избыточно - можно использовать date(begdt)?');

            $table->dateTime('wrkbegdt')->nullable()->comment('начало работы');
            $table->dateTime('wrkenddt')->nullable()->comment('завершение работы');
            $table->decimal('wrkhrs',4,1)->nullable()->comment('кол-во рабочих часов');
            $table->decimal('hr_cost',10,2)->nullable()->comment('ставка за рабочий час');

            $table->tinyinteger('raid_qty')->nullable()->unsigned()->comment('Кол-во рейсов');
            $table->decimal('raid_sum',12,2)->nullable()->comment('Сумма ЗП за рейсы');

            $table->decimal('pdt_hrs',4,1)->nullable()->comment('кол-во часов простоя по уважительной причине - paid_downtime_hours');
            $table->decimal('pdt_cost',10,2)->nullable()->comment('ставка за час простоя');
            $table->decimal('pdt_sum',12,2)->default(0)->comment('Сумма ЗП за простой');

            $table->decimal('repair_hrs',4,1)->nullable()->comment('кол-во часов ремонта');
            $table->decimal('repair_cost',10,2)->nullable()->comment('ставка за час ремонта');
            $table->decimal('repair_sum',12,2)->default(0)->comment('Сумма ЗП за ремонт');

            $table->decimal('salary_sum',12,2)->default(0)->comment('Общая сумма ЗП');

            $table->decimal('meter_begqty',12,1)->nullable()->comment('Показания спидометра на начало, км');
            $table->decimal('meter_endqty',12,1)->nullable()->comment('Показания спидометра на конец, км');
            $table->decimal('meter_qty',12,1)->nullable()->comment('Кол-во пройденных км');

            $table->integer('fuel_begqty')->nullable()->comment('Кол-во топлива на начало, л');
            $table->integer('fuel_inpqty')->nullable()->default(0)->comment('Получено за период, л');
            $table->integer('fuel_endqty')->nullable()->comment('Кол-во топлива на конец, л');
            $table->integer('fuel_spentqty')->nullable()->comment('Кол-во израсходованного топлива за период работы, л');

            $table->decimal('mchnwrkhrs',4,1)->nullable()->comment('кол-во рабочих часов техники');

            $table->string('notes',300)->nullable()->comment('Примечание');

            $table->boolean('active')->default(true);
            $table->boolean('locked')->default(false);
            $table->boolean('statusid')->default(0);


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
        Schema::dropIfExists('driver_works');
    }
}
