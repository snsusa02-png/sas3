<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMchnrqstFactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mchnrqst_facts', function (Blueprint $table) {

            $table->id();

            $table->biginteger('rqstid')->unsigned()->nullable()->comment('ID заявки по mchnrqsts.id');

	    //Регистрация фактических данных по исполнению заявки
            $table->biginteger('machineid')->unsigned()->nullable()->index()->comment('Факт. использованный экземпляр (своей) спецтехники');
            $table->string('machinename',160)->nullable()->comment('Название/описание использованной техники - от стороннего владельца');

            $table->dateTime('fctbegdt')->nullable();
            $table->dateTime('fctenddt')->nullable();

            $table->biginteger('fct_userid')->unsigned()->nullable()->index()->comment('Регистратор фактических данных');
            $table->timestamp('fct_at')->nullable()->comment('Время регистрации факта');

//            $table->string('drivername',60)->nullable()->comment('ФИО водителя');
//            $table->biginteger('driver_orgid')->unsigned()->nullable()->index()->comment('организация водителя (ИП)');

            $table->string('drivername',60)->nullable()->comment('ФИО водителя');
            $table->biginteger('driver_orgid')->unsigned()->nullable()->index()->comment('организация водителя (ИП)');
            $table->biginteger('driver_contractid')->unsigned()->nullable()->index()->comment('id договора с перевозчиком (источник цен для driver_price/driver_sum)');
	    $table->decimal('driver_price', 12,2)->nullable()->comment('Цена от перевозчика (ИП по договору id=1) для заказчика-посредника');
	    $table->decimal('driver_sum', 12,2)->nullable()->comment('Общая стоимость для заказчика-посредника от организации-перевозчика car_orgid');

            $table->decimal('fcthrs', 6,1)->nullable()->comment('фактически отработано кол-во часов - устарело, заменим на fct_qty');
            $table->string('fct_priceunit',36)->nullable()->comment('В чем измеряется фактическое количество');
            $table->decimal('fct_qty', 6,1)->nullable()->comment('фактически отработано кол-во (fct_priceunit)');
	    $table->decimal('fct_price',12,2)->nullable()->comment('Цена за fct_priceunit от фактического перевозчика');
	    $table->decimal('fct_sum',12,2)->nullable()->comment('Общая стоимость от фактического перевозчика');

            $table->decimal('add_vatrate', 6,1)->nullable()->default(0)->comment('Возрастание факт. цены на ставку НДС');
            $table->decimal('add_markup', 6,1)->nullable()->default(0)->comment('Возрастание факт. цены на интерес организатора');
	    $table->decimal('own_price', 12,2)->nullable()->comment('Цена для заказчика = fct_price * (100+add_vatrate+add_markup)/100');
	    $table->decimal('own_sum', 12,2)->nullable()->comment('Общая стоимость для заказчика от ownorgid');

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
        Schema::dropIfExists('mchnrqst_facts');
    }
}
