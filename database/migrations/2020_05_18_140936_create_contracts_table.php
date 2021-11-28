<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {

            $table->id();

            $table->biginteger('ownorgid')->unsigned()->index();
            $table->biginteger('orgid')->unsigned()->nullable()->index()->comment('');
            $table->biginteger('acsid')->unsigned()->default(1)->index()->comment('категория информации - для доступа');

            $table->biginteger('buildobjid')->unsigned()->nullable()->index()->comment('Id объекта BuildObjs');

            $table->string('name',210)->nullable()->comment('Название документа');
            $table->string('docnum',36)->nullable();
            $table->date('docdate')->nullable();

            $table->biginteger('regnum_srcid')->unsigned();
	            $table->foreign('regnum_srcid')->references('id')->on('regnum_srcs')->onDelete('cascade');

            $table->integer('regnum_num')->unsigned()->nullable()->comment('чистый номер регистрации');
            $table->string('regnum',36)->nullable()->comment('Регистрационный номер');

            $table->dateTime('begdate')->nullable();
            $table->dateTime('enddate')->nullable();
            $table->decimal('docsum',12,2)->nullable()->comment('Сумма договора, с НДС');
            $table->decimal('advance_pcnt',5,1)->nullable()->comment('Аванс, %');
            $table->decimal('advance_sum',12,2)->nullable()->comment('Аванс сумма, руб');

            $table->biginteger('doc_templateid')->unsigned()->nullable()->comment('id типового шаблона договора');

            $table->biginteger('categoryid')->unsigned()->nullable()->index()->comment('Доходный/Расходный/Кредит');
		$table->foreign('categoryid')->references('id')->on('contract_categories')->comment('Категория договора');

            $table->biginteger('contracttypeid')->unsigned()->nullable()->index()->comment('');
            $table->string('contracttypename',120)->nullable()->comment('тип договора');

            $table->string('reason',100)->nullable()->comment('Основание');
            $table->string('descript',300)->nullable()->comment('Описание, суть договора');
            $table->string('notes',300)->nullable()->comment('Примечание');

            $table->string('price_unit',16)->nullable()->default('час')->comment('ЕИ объемов работ для вычисления стоимости работ');

            $table->boolean('active')->default(true);
            $table->boolean('signed')->default(true)->comment('1-Подписан (действующий)');

            $table->tinyInteger('statusid')->nullable()->unsigned()->default(0)
		->comment('0-черновик; 2-проект; 4-действует; 6-отменен; 8-исполнен; ');
            $table->string('status_notes',160)->nullable()->comment('Пояснение к статусу');

            $table->biginteger('opposite_docid')->unsigned()->nullable()->comment('id записи о контракте для второй стороны (Если обе входят в холдинг)');
//		$table->foreign('opposite_docid')->references('id')->on('contracts')->onDelete('set null');

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
        Schema::dropIfExists('contracts');
    }
}
