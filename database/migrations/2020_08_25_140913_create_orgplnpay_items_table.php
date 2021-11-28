<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrgplnpayItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orgplnpay_items', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('docid')->unsigned();
		$table->foreign('docid')->references('id')->on('orgplnpays')->comment('Документ');

            $table->bigInteger('inituserid')->unsigned()->nullable()->comment('Инициатор помещения в план платежей');

            $table->bigInteger('orgid')->unsigned();
		$table->foreign('orgid')->references('id')->on('orgs')->comment('Получатель');

            $table->bigInteger('contractid')->unsigned();
		$table->foreign('contractid')->references('id')->on('contracts')->comment('Договор с контрагентом');

            $table->bigInteger('categoryid')->unsigned()->nullable();
		$table->foreign('categoryid')->references('id')->on('pay_categories')->comment('Вид платежа');

            $table->biginteger('src_sysobjid')->unsigned()->nullable()->comment('sysobjid исходной записи');
		$table->foreign('src_sysobjid')->references('id')->on('sysobjs');
            $table->biginteger('src_objid')->unsigned()->nullable()->comment('');

            $table->string('reason',160)->nullable()->comment('Основание оплаты');
            $table->decimal('plnpaysum',12,2)->nullable()->comment('Планируемая сумма оплаты');
            $table->date('needpay_before')->nullable()->comment('Нужно оплатить до указанной даты');
            $table->integer('ordr')->unsigned()->default(999999);
            $table->string('notes',160)->nullable()->comment('Примечания');

            $table->date('limpaydate')->nullable()->comment('Предельная дата оплаты');

            $table->boolean('have_funding')->default(false)->comment('Есть финансирование');

            $table->bigInteger('equiprqst_id')->unsigned()->nullable()->comment('ID заявки на материалы');
//		$table->foreign('equiprqst_id')->references('id')->on('equiprqsts');

            $table->decimal('agr1_sum',12,2)->nullable()->comment('Согласованная сумма платежа 1-го согласователя');
            $table->bigInteger('agr1_by')->unsigned()->nullable()->comment('Согласователь 1');
            $table->timestamp('agr1_at')->nullable();
            $table->string('agr1_notes',160)->nullable()->comment('Примечания согласователя 1');

            $table->decimal('agr2_sum',12,2)->nullable()->comment('Согласованная сумма платежа 2-го согласователя');
            $table->bigInteger('agr2_by')->unsigned()->nullable()->comment('Согласователь 2');
            $table->timestamp('agr2_at')->nullable();
            $table->string('agr2_notes',160)->nullable()->comment('Примечания согласователя 2');

            $table->boolean('approved')->default(false);

            $table->decimal('fctpaysum',12,2)->nullable()->comment('Фактическая сумма оплаты');

            $table->bigInteger('orgacntid')->nullable()->unsigned()->comment('р/счет с которого произвели оплату');
		$table->foreign('orgacntid')->references('id')->on('org_acnts');

            $table->bigInteger('initpay_by')->unsigned()->nullable()->comment('Регистратор отправки требования в банк');
            $table->timestamp('initpay_at')->nullable()->comment('Момент отправки требования в банк');

            $table->bigInteger('fctpay_by')->unsigned()->nullable()->comment('Регистратор проведенной оплаты');
            $table->timestamp('fctpay_at')->nullable();

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
        Schema::dropIfExists('orgplnpay_items');
    }
}
