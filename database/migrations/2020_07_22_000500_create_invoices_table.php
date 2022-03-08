<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();


            $table->bigInteger('src_orgid')->unsigned();
		$table->foreign('src_orgid')->references('id')->on('orgs')->comment('Кто выставил счет');

            $table->bigInteger('tgt_orgid')->unsigned();
		$table->foreign('tgt_orgid')->references('id')->on('orgs')->comment('Кому выставлен счет');

            $table->bigInteger('ownorgid')->unsigned();
		$table->foreign('ownorgid')->references('id')->on('orgs')->comment('Организация, принявшая документ к учету');

//            $table->bigInteger('orgid')->unsigned();
//		$table->foreign('orgid')->references('id')->on('orgs')->comment('');

            $table->bigInteger('contractid')->unsigned();
//		$table->foreign('contractid')->references('id')->on('contracts')->comment('Договор с контрагентом');

//            $table->biginteger('buildobjid')->unsigned()->nullable()->index()->comment('Объект ID по BuildObjs.ID');
//		$table->foreign('buildobjid')->references('id')->on('buildobjs')->comment('');

            $table->biginteger('opertypeid')->unsigned()->nullable()->comment('Вид работ по OperTypes.ID');
		$table->foreign('opertypeid')->references('id')->on('opertypes')->comment('');

//            $table->bigInteger('exe_orgid')->unsigned();
//		$table->foreign('exe_orgid')->references('id')->on('orgs')->comment('Кто подрядчик');

//            $table->bigInteger('exe_contractid')->unsigned();
//		$table->foreign('exe_contractid')->references('id')->on('contracts')->comment('Договор с подрядчиком');

//            $table->bigInteger('orgacntid')->nullable()->unsigned();
//		$table->foreign('orgacntid')->references('id')->on('org_acnts')->comment('р/счет');

//            $table->bigInteger('for_orgid')->unsigned()->nullable();
//		$table->foreign('for_orgid')->references('id')->on('orgs')->comment('Для кого выписан счет. Конечный покупатель');

            $table->bigInteger('inituserid')->nullable()->unsigned();
		$table->foreign('inituserid')->references('id')->on('users')->comment('Инициатор/Куратор');

            $table->bigInteger('pardocid')->nullable()->unsigned();
		$table->foreign('pardocid')->references('id')->on('invoices')->comment('Прародитель');

            $table->bigInteger('doctypeid')->nullable()->unsigned()->default(1)->comment('1-счет; 2-УПД');

            $table->string('docnum',36)->nullable()->comment('№ документа');
            $table->date('docdate')->nullable()->comment('Дата документа');
            $table->date('enddate')->nullable()->comment('Окончание действия документа, включительно до 23:59:59');
            $table->date('fullpaydate')->nullable()->comment('Дата полной оплаты счета. Полная если сумма оплаты == сумме использования');
//            $table->date('getdate')->nullable()->comment('Дата получения (материалов/документа)');

            $table->decimal('docsum',12,2)->nullable()->comment('Сумма счета');
            $table->decimal('usedsum',12,2)->nullable()->comment('Сумма использования счета');

            $table->decimal('aux_sum',12,2)->nullable()->comment('Сумма доп. затрат');
            $table->string('aux_descript',45)->nullable()->comment('Описание доп. затрат');

            $table->string('reason',60)->nullable()->comment('Основание (договор поставки)');
            $table->string('notes',160)->nullable()->comment('Примечания');

            $table->bigInteger('categoryid')->unsigned()->nullable();
		$table->foreign('categoryid')->references('id')->on('pay_categories')->comment('Вид платежа');

//            $table->Integer('plngetwrkdays')->nullable()->unsigned()->comment('Примерный срок получения в рабочих днях от даты оплаты');

            $table->boolean('active')->default(true);
            $table->boolean('locked')->default(false)->comment('Блокирован от изменений');
            $table->string('status',60)->nullable()->comment('Описание текущего состояния');

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
        Schema::dropIfExists('invoices');
    }
}
