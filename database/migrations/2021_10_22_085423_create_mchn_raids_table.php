<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMchnRaidsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mchn_raids', function (Blueprint $table) {

            $table->id();

            $table->biginteger('dw_id')->unsigned()->nullable()->comment('Связка с отчетом о работе');
		$table->foreign('dw_id')->references('id')->on('driver_works');

            $table->biginteger('opertypeid')->unsigned()->nullable()->comment('Вид Работ');
		$table->foreign('opertypeid')->references('id')->on('opertypes');

            $table->biginteger('machineid')->unsigned()->nullable()->comment('Техника');
		$table->foreign('machineid')->references('id')->on('machines');

            $table->string('machinename',160)->nullable()->comment('Название/описание использованной техники - от стороннего владельца');

            $table->biginteger('mot_id')->unsigned()->nullable()->comment('Режим эксплуатации');
		$table->foreign('mot_id')->references('id')->on('mchn_opertypes');

            $table->date('wrkdate')->comment('Дата работы');
            $table->dateTime('wrkbegdt')->nullable()->comment('Время начала работы');
            $table->dateTime('wrkenddt')->nullable()->comment('Время окончания работы');

            $table->decimal('stfwrkhrs',4,1)->nullable()->comment('кол-во рабочих часов работника');
            $table->decimal('mchnwrkhrs',4,1)->nullable()->comment('кол-во рабочих часов техники');

//            $table->dateTime('begdt')->comment('начало работы');
//            $table->dateTime('enddt')->nullable()->comment('завершение работы');
//            $table->decimal('wrkhrs',4,1)->nullable()->comment('кол-во рабочих часов');

//            $table->decimal('hour_work_cost',10,2)->nullable()->comment('Стоимость 1 часа работ');
//            $table->decimal('hour_fuel_cost',10,2)->nullable()->comment('Стоимость 1 часа на топливо');


            $table->string('drivername',60)->nullable()->comment('ФИО водителя');
            $table->biginteger('driverid')->unsigned()->nullable()->comment('Водитель/Машинист');
		$table->foreign('driverid')->references('id')->on('orgstaff');

            $table->biginteger('driver_orgid')->unsigned()->nullable()->index()->comment('организация водителя (ИП)');
            $table->biginteger('driver_contractid')->unsigned()->nullable()->index()->comment('id договора с перевозчиком (источник цен для driver_price/driver_sum)');

	    $table->decimal('driver_price', 12,2)->nullable()->comment('Цена от перевозчика (ИП по договору id=1) для заказчика-посредника');
	    $table->decimal('driver_sum', 12,2)->nullable()->comment('Общая стоимость для заказчика-посредника от организации-перевозчика car_orgid');

            $table->integer('raid_qty')->unsigned()->nullable()->comment('Кол-во рейсов');
            $table->decimal('raid_salary',12,2)->nullable()->comment('Сумма ЗП за 1 рейс. Общая сумма = raid_qty*raid_salary');


	    // Покупка у поставщика для перепродажи клиенту	
            $table->biginteger('suporgid')->unsigned()->index()->comment('Поставщик')->nullable();
	            $table->foreign('suporgid')->references('id')->on('orgs');

            $table->biginteger('load_placeid')->unsigned()->nullable()->comment('ID места погрузки по org_places');
            $table->string('load_placename',60)->nullable()->comment('Название(адрес) места погрузки');
	
            $table->biginteger('load_ownorgid')->unsigned()->index()->comment('Кто купил у Поставщика');
	            $table->foreign('load_ownorgid')->references('id')->on('orgs');


            $table->biginteger('load_refitmid')->unsigned()->nullable()->comment('ID товара/груза по прайсу поставщика');

            $table->biginteger('qty_unittypeid')->unsigned()->nullable()->comment('В чем измеряется количество');
            $table->string('qty_unit',36)->nullable()->comment('В чем измеряется количество');

            $table->decimal('load_qty', 8,2)->nullable()->comment('кол-во загруженного (в единицах qty_unit)');
            $table->decimal('load_price', 12,2)->nullable()->comment('цена загрузки, руб');
            $table->decimal('load_sum', 12,2)->nullable()->comment('сумма загруженного, руб');

            $table->biginteger('unload_refitmid')->unsigned()->nullable()->comment('ID товара/груза по прайсу поставщика');
            //$table->biginteger('refitmid')->unsigned()->nullable()->comment('ID товара/груза');
            //$table->string('cargo_name',60)->nullable()->comment('Название груза');
		
            $table->decimal('ownorg_sum', 12,2)->nullable()->comment('Сумма переуступки между компаниями ГК load_ownorgid и ownorgid. В интервале между load_sum и unload_sum');

            //Продажа клиенту
            $table->biginteger('unload_ownorgid')->unsigned()->nullable()->comment('Кто (пере)продал товар Клиенту (от ГК)');
		$table->foreign('unload_ownorgid')->references('id')->on('orgs');

            $table->biginteger('orgid')->unsigned()->nullable()->comment('Организация-клиент (заказчик)');
		$table->foreign('orgid')->references('id')->on('orgs');
            $table->string('org_name',60)->nullable()->comment('Название организации-подрядчика');

            $table->biginteger('unload_placeid')->unsigned()->nullable()->comment('ID места выгрузки по org_places');
            $table->string('unload_placename',60)->nullable()->comment('Название(адрес) места выгрузки');

            $table->decimal('unload_qty', 8,2)->nullable()->comment('кол-во переданного клиенту (в единицах qty_unit)');
            $table->decimal('unload_price', 12,2)->nullable()->comment('цена отпуска, руб');
            $table->decimal('unload_sum', 12,2)->nullable()->comment('сумма загруженного, руб');

	    $table->biginteger('paytypeid')->unsigned()->nullable()->comment('ID типа оплаты 1-б/нал, 2-нал');


            $table->biginteger('disp_staffid')->unsigned()->nullable()->comment('Диспетчер (сотрудник)');
		$table->foreign('disp_staffid')->references('id')->on('orgstaff');

            $table->string('notes',300)->nullable()->comment('Примечание');
            $table->boolean('active')->default(true);
        



//          $table->decimal('fct_qty', 6,1)->nullable()->comment('фактически отработано кол-во (fct_priceunit)');
//	    $table->decimal('fct_price',12,2)->nullable()->comment('Цена за fct_priceunit от фактического перевозчика');
//	    $table->decimal('fct_sum',12,2)->nullable()->comment('Общая стоимость от фактического перевозчика');

//            $table->biginteger('buildopertypeid')->unsigned()->nullable()->comment('Вид Работ');
//		$table->foreign('buildopertypeid')->references('id')->on('buildopertypes');

//            $table->biginteger('contractid')->unsigned()->nullable()->comment('Договор подряда');
//		$table->foreign('contractid')->references('id')->on('contracts');

//            $table->biginteger('bdgtitmsumid')->unsigned()->nullable()->comment('Статья бюджета');
//		$table->foreign('bdgtitmsumid')->references('id')->on('budget_itmsums');


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
        Schema::dropIfExists('mchn_raids');
    }
}
