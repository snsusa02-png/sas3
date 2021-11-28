<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquiprqstsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('equiprqsts', function (Blueprint $table) {
            $table->id();

            $table->string('name',160)->nullable()->comment('Название');

            $table->tinyinteger('stageid')->unsigned()->default(1)->comment('1-черновик; 2-Подана; 3-Взята в работу; 4-Размещен заказ поставщику; 5-Получено; 6-Передано');
            $table->datetime('stage_begdt')->nullable()->comment('Момент передачи на этап');
            $table->tinyinteger('statusid')->unsigned()->default(1)->comment('');

            $table->date('docdate')->nullable()->comment('Дата заявки');
            $table->string('docnum',36)->nullable()->comment('№ заявки');

            $table->biginteger('projid')->unsigned()->nullable()->index()->comment('Проект');
		$table->foreign('projid')->references('id')->on('projects');

            $table->biginteger('buildobjid')->unsigned()->nullable()->index()->comment('Объект');
		$table->foreign('buildobjid')->references('id')->on('buildobjs');

//            $table->biginteger('budgetid')->unsigned()->nullable()->index()->comment('Бюджет');
//		$table->foreign('budgetid')->references('id')->on('budgets');

//            $table->biginteger('budgetitmid')->unsigned()->nullable()->index()->comment('Позиция бюджета');
//		$table->foreign('budgetitmid')->references('id')->on('budget_items');

            $table->biginteger('buildopertypeid')->unsigned()->nullable()->index()->comment('Вид работ');
		$table->foreign('buildopertypeid')->references('id')->on('buildopertypes');

            $table->string('deli_address',160)->nullable()->comment('Адрес доставки');

//            $table->biginteger('payorgid')->unsigned()->nullable()->index()->comment('Плательщик');
//		$table->foreign('payorgid')->references('id')->on('orgs');

            $table->biginteger('pln_suporgid')->unsigned()->nullable()->index()->comment('Предпочтительная компания-закупщик (снабжение)');
		$table->foreign('pln_suporgid')->references('id')->on('orgs');

            $table->biginteger('lim_budgetownerid')->unsigned()->nullable()->index()->comment('ограничение на владельца бюджета');
		$table->foreign('lim_budgetownerid')->references('id')->on('orgs');

            $table->timestamp('init_at')->nullable()->comment('Момент подачи заявки');
            $table->biginteger('inituserid')->unsigned()->nullable()->index()->comment('Инициатор - пользователь');
		$table->foreign('inituserid')->references('id')->on('users');

            $table->biginteger('initstaffid')->unsigned()->nullable()->index()->comment('Инициатор - сотрудник');
		$table->foreign('initstaffid')->references('id')->on('orgstaff');

            $table->biginteger('initorgid')->unsigned()->nullable()->index()->comment('Инициатор (нужен?)');
		$table->foreign('initorgid')->references('id')->on('orgs');

            $table->string('init_reason',160)->nullable()->comment('Основание для заявки');

            $table->biginteger('orgid')->unsigned()->nullable()->index()->comment('Подрядчик');
		$table->foreign('orgid')->references('id')->on('orgs');

            $table->biginteger('contractid')->unsigned()->nullable()->index()->comment('договор подряда/бюджета');
		$table->foreign('contractid')->references('id')->on('contracts');

            $table->biginteger('chkorgid')->unsigned()->nullable()->index()->comment('Инспектор/Проверяющий');
		$table->foreign('chkorgid')->references('id')->on('orgs');

            $table->biginteger('finuserid')->unsigned()->nullable()->index()->comment('Финансист - пользователь');
		$table->foreign('finuserid')->references('id')->on('users');

            $table->decimal('finlimsum',12,2)->nullable()->comment('Лимит бюджета');
            $table->timestamp('finlim_at')->nullable();

            $table->biginteger('exeorgid')->unsigned()->nullable()->index()->comment('Исполнитель');
		$table->foreign('exeorgid')->references('id')->on('orgs');

            $table->biginteger('exeuserid')->unsigned()->nullable()->index()->comment('Исполнитель - пользователь');
		$table->foreign('exeuserid')->references('id')->on('users');

            $table->biginteger('exestaffid')->unsigned()->nullable()->index()->comment('Исполнитель - сотрудник');
		$table->foreign('exestaffid')->references('id')->on('orgstaff');

            $table->timestamp('exebeg_at')->nullable()->comment('взята в работу');

            $table->string('notes',360)->nullable()->comment('Примечание');

            $table->boolean('active')->default(true);

            $table->decimal('plnordsum',12,2)->nullable()->comment('Ожидаемая сумма заказа (от поставщика)');
            $table->decimal('plnauxsum',12,2)->nullable()->comment('Ожидаемые доп. расходы (доставка и т.п.)');
            $table->timestamp('plnord_at')->nullable();

            $table->boolean('pay_dcsn')->nullable()->comment('1-платим, 0 - не платим');
            $table->date('plnpaydate')->nullable()->comment('Планируемая дата оплаты');
            $table->biginteger('plnpay_by')->unsigned()->nullable()->index()->comment('Кто принял решение оплатить');
		$table->foreign('plnpay_by')->references('id')->on('users');
            $table->timestamp('plnpay_at')->nullable();

            $table->biginteger('equiprqstid')->unsigned()->nullable()->index()->comment('Связка с заявкой на мат-лы');
		$table->foreign('equiprqstid')->references('id')->on('equiprqsts');


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
        Schema::dropIfExists('equiprqsts');
    }
}
