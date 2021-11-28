<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEritmOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('eritm_offers', function (Blueprint $table) {
            $table->id();
            $table->biginteger('eritmid')->unsigned()->index();
		$table->foreign('eritmid')->references('id')->on('equiprqst_items')->onDelete('cascade');

            $table->integer('ordr')->default(1);

            $table->bigInteger('invoiceid')->nullable()->unsigned()->comment('счет поставщика по системе Invoices')->index();
//		$table->foreign('invoiceid')->references('id')->on('invoices');

            $table->bigInteger('invitmid')->nullable()->unsigned()->comment('ссылка на позицию счета поставщика')->index();
		//$table->foreign('invitmid')->references('id')->on('invoice_items');

            $table->bigInteger('suporgid')->nullable()->unsigned()->comment('выбранный поставщик')->index();
//		$table->foreign('suporgid')->references('id')->on('orgs');

            $table->bigInteger('refitmid')->nullable()->unsigned()->comment('id по спр-ку refitmes')->index();
//		$table->foreign('refitmid')->references('id')->on('users');

            $table->string('itmname',300)->nullable()->comment('Наименование материала/оборудования по счету поставщика');
            $table->string('itmdescript',300)->nullable()->comment('Доп. информация');

            $table->string('doc_unit',16)->nullable()->default('шт')->comment('Единица измерения по документу постащика');
            $table->decimal('doc_qty',10,3)->nullable()->default(0)->comment('Кол-во по документу поставщика в ЕИ поставщика');
            $table->decimal('doc_price',13,6)->nullable()->comment('Цена поставщика за 1ЕИ поставщика, руб');
            $table->decimal('doc_sum',13,6)->nullable()->comment('Сумма позиции по док-ту поставщика, руб');

            $table->decimal('ord_qty',10,3)->nullable()->comment('Количество в ЕИ заявки');
            $table->decimal('ord_price',13,6)->nullable()->comment('Цена поставщика за 1ЕИ заявки, руб');
            $table->decimal('ord_sum',12,2)->nullable()->comment('Сумма поставщика, руб. Должна быть равна doc_sum');
            $table->decimal('ord_auxsum',12,2)->nullable()->comment('Сумма доп. затрат. Для определения фактической цены');
            $table->decimal('ord_fctprice',13,6)->nullable()->comment('Фактическая цена с учетом доп. затрат, руб');

            $table->decimal('ordpay_sum',12,2)->nullable()->comment('Сумма оплаты поставщику, руб');
            $table->date('ordpay_date')->nullable()->comment('?Дата оплаты поставщику');

            $table->date('plngetdate')->nullable()->comment('Ожидаемая дата получения от поставщика');
            $table->bigInteger('plngetwrkdays')->nullable()->unsigned()->comment('Примерный срок получения в рабочих днях от даты оплаты');
//            $table->date('fctgetdate')->nullable()->comment('Фактическая дата получения от поставщика');

            $table->decimal('get_qty',10,3)->nullable()->default(0)->comment('Сводное кол-во, полученное от поставщика, ЕИ заявки');
            $table->decimal('m15_qty',10,3)->nullable()->default(0)->comment('Передано заказчику (orgid) по формам М15, ЕИ');

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
        Schema::dropIfExists('eritm_offers');
    }
}
