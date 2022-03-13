<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMrOpersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mr_opers', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('mr_id')->unsigned()->comment('ID записи о рейсах mchn_raids.id');
	        $table->foreign('mr_id')->references('id')->on('mchn_raids');

            $table->tinyinteger('sale_dir')->comment('-1 - покупка, 0 - внутренняя операция, +1 - продажа на сторону');

            $table->string('name', 60)->nullable()->comment('Название, суть операции');

            $table->biginteger('disp_staffid')->unsigned()->nullable()->comment('Диспетчер (сотрудник)');
		$table->foreign('disp_staffid')->references('id')->on('orgstaff');

	    //поставщик товара / услуги
            $table->biginteger('suporgid')->unsigned()->index()->comment('Поставщик/Получатель оплаты');
	            $table->foreign('suporgid')->references('id')->on('orgs');
            $table->boolean('sup_gk')->comment('0-поставщик внешний; 1-поставщик входит в ГК');
            $table->biginteger('sup_placeid')->unsigned()->nullable()->comment('ID места предоставления услуги поставщиком');
            $table->string('sup_placename',60)->nullable()->comment('Название(адрес) места погрузки');

	    //получатель услуги/товара - плательщик
            $table->biginteger('orgid')->unsigned()->index()->comment('Кто купил у Поставщика/Плательщик');
	            $table->foreign('orgid')->references('id')->on('orgs');

	    $table->biginteger('contractid')->unsigned()->index();
		$table->foreign('contractid')->references('id')->on('contracts');

            $table->boolean('org_gk')->comment('0-покупатель внешний; 1-покупатель входит в ГК');
            $table->biginteger('org_placeid')->unsigned()->nullable()->comment('ID места выгрузки по org_places');
            $table->string('org_placename',60)->nullable()->comment('Название(адрес) места выгрузки');


            $table->biginteger('refitmid')->unsigned()->nullable()->comment('ID товара/услуги по прайсу поставщика');

            $table->biginteger('qty_unittypeid')->unsigned()->nullable()->comment('В чем измеряется количество');
            $table->string('qty_unit',36)->nullable()->comment('В чем измеряется количество');

            $table->decimal('itm_qty', 8,2)->comment('кол-во товара (в единицах qty_unit)');
            $table->decimal('itm_price', 12,2)->comment('цена товара/услуги, руб');
            $table->decimal('itm_sum', 12,2)->comment('сумма за товар/услугу, руб');


	    $table->biginteger('paytypeid')->unsigned()->nullable()->comment('ID типа оплаты 1-б/нал, 2-нал');

            $table->decimal('agent_sum', 12,2)->nullable()->comment('Вознаграждение агента, руб');
	    $table->biginteger('agentid')->unsigned()->nullable()->comment('ID агента (по Agents.id?)');

	    $table->biginteger('drvsum_calctypeid')->unsigned()->nullable()->comment('ID типа расчета ЗП водителя (driver_sum) 1-ручной, 2-базовая схема, 3-схема ГК Восток');
            $table->decimal('driver_sum', 12,2)->nullable()->comment('ЗП водителя от операции, руб');


            $table->boolean('active')->default(1)->comment('0-черновик; 1-используется в расчетах');
            $table->integer('ordr')->unsigned()->default(1);

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
        Schema::dropIfExists('mr_opers');
    }
}
