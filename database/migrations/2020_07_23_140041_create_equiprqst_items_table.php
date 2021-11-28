<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquiprqstItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('equiprqst_items', function (Blueprint $table) {
            $table->id();
            $table->biginteger('rqstid')->unsigned()->index();
		$table->foreign('rqstid')->references('id')->on('equiprqsts')->onDelete('cascade');

            $table->integer('ordr')->default(1);

            $table->bigInteger('lim_refitmid')->nullable()->unsigned()->comment('id номенклатуры лимита по ВР')->index();
		$table->foreign('lim_refitmid')->references('id')->on('refitems');

            $table->bigInteger('itmtypeid')->nullable()->unsigned()->comment('id по спр-ку itmtypes')->index();
		$table->foreign('itmtypeid')->references('id')->on('itmtypes');

            $table->bigInteger('refitmid')->nullable()->unsigned()->comment('id по спр-ку refitmes')->index();
		$table->foreign('refitmid')->references('id')->on('refitems');

            $table->string('itmname',300)->nullable()->comment('Наименование материала/оборудования');
            $table->string('itmdescript',500)->nullable()->comment('Требования к материалу/оборудованию');
            $table->string('supdescript',160)->nullable()->comment('Описание темпа/графика поставок');

            $table->string('manufacturer',60)->nullable()->comment('Производитель');
            $table->string('mfq_partnumber',36)->nullable()->comment('Код товара по каталогу производителя');

            $table->bigInteger('unittypeid')->unsigned()->nullable()->comment('ID единицы измерения (по UnitTypes.id)');
		$table->foreign('unittypeid')->references('id')->on('unittypes');
            $table->string('unit', 16)->nullable()->default('шт')->comment('Единица измерения');

            $table->decimal('rqst_qty',10,3)->nullable()->default(0)->comment('Требуемое кол-во, ЕИ');
            $table->date('minreqdate')->nullable()->comment('Дата получения - минимум');
            $table->date('maxreqdate')->nullable()->comment('максимально-допустимая дата получения');

 	   $table->biginteger('bdgtitmsumid')->unsigned()->nullable()->index()->comment('источник финансирования');
		$table->foreign('bdgtitmsumid')->references('id')->on('budget_itmsums');

 	   $table->biginteger('bdgtorgid')->unsigned()->nullable()->index()->comment('ЦФО - владелец бюджета');
		$table->foreign('bdgtorgid')->references('id')->on('orgs');

 	   $table->biginteger('m15srcorgid')->unsigned()->nullable()->index()->comment('Организация - давальщик материалов');
		$table->foreign('m15srcorgid')->references('id')->on('orgs');

 	   $table->biginteger('m15tgtorgid')->unsigned()->nullable()->index()->comment('Организация - получатель матенриалов');
		$table->foreign('m15tgtorgid')->references('id')->on('orgs');

 	   $table->biginteger('bdgtacnttypeid')->unsigned()->nullable()->index()->comment('тип статьи расхода/дохода');
		$table->foreign('bdgtacnttypeid')->references('id')->on('bdgtacnttypes');

            $table->string('rqst_status',36)->nullable()->comment('Текущий статус обработки');

            $table->decimal('est_price',12,2)->nullable()->comment('Оценочная цена, руб');
            $table->bigInteger('est_price_by')->nullable()->unsigned()->comment('Кто (UserID) оценил стоимость товара');
            $table->timestamp('est_price_at')->nullable()->comment('Когда произведена оценка');
            $table->string('est_notes',160)->nullable()->comment('Доп. сведения по оценке: поставщик, сроки/условия поставки');

 	    $table->tinyinteger('suppaytypeid')->unsigned()->nullable()->comment('Предполагаемая оценка оплаты 1-предоплата, 2-постоплата');
            
            $table->string('smet_price_calc',36)->comment('Формула расчета цены сметы')->nullable();
            $table->decimal('smet_price',12,2)->nullable()->comment('Цена по смете, руб');

            $table->bigInteger('invoiceid')->nullable()->unsigned()->comment('счет поставщика по системе Invoices')->index();
		$table->foreign('invoiceid')->references('id')->on('invoices');

            $table->bigInteger('suporgid')->nullable()->unsigned()->comment('выбранный поставщик')->index();
		$table->foreign('suporgid')->references('id')->on('orgs');

            $table->tinyinteger('ord_decision')->nullable()->default(1)->comment('0-отказ, 1-заказ');

            $table->timestamp('rejected_at')->nullable();
            $table->bigInteger('rejected_by')->nullable()->unsigned()->comment('Кто отказал');
            $table->string('reject_reason',160)->nullable()->comment('Причина отказа по позиции');

            $table->date('suporddate')->nullable()->comment('Дата заказа у поставщика');

            $table->decimal('ord_qty',10,3)->nullable()->comment('Заказанное кол-во у поставщика, ЕИ');
            $table->decimal('ord_price',13,6)->nullable()->comment('Цена поставщика, руб');
            $table->decimal('ord_sum',12,2)->nullable()->comment('Сумма поставщика, руб');

            $table->decimal('ordpay_sum',12,2)->nullable()->comment('Сумма оплаты поставщику, руб');
            $table->date('ordpay_date')->nullable()->comment('Дата оплаты поставщику');

            $table->date('plngetdate')->nullable()->comment('Ожидаемая дата получения от поставщика');
            $table->date('fctgetdate')->nullable()->comment('Фактическая дата получения от поставщика');

            $table->decimal('out_price',12,2)->nullable()->comment('Отпускная цена, от МТС - заказчику, руб');

            $table->decimal('get_qty',10,3)->nullable()->default(0)->comment('Получено от поставщика, ЕИ');

            $table->decimal('dlvrd_qty',10,3)->nullable()->default(0)->comment('Передано заказчику, ЕИ');

            $table->decimal('m15_qty',10,3)->nullable()->default(0)->comment('Передано заказчику (orgid) по формам М15, ЕИ');

            $table->decimal('noreq_qty',10,3)->nullable()->default(0)->comment('Уже не требуется, ЕИ');
            $table->string('noreq_reason',160)->nullable()->comment('Причина отказа от полного количества');
            $table->timestamp('noreq_at')->nullable();
            $table->bigInteger('noreq_by')->nullable()->unsigned()->comment('Кто отказал');

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
        Schema::dropIfExists('equiprqst_items');
    }
}
