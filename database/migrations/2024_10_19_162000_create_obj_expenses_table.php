<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obj_expenses', function (Blueprint $table) {

            $table->id();
            $table->biginteger('sysobjid')->unsigned()->nullable();
	        $table->foreign('sysobjid')->references('id')->on('sysobjs')->onDelete('cascade')->onUpdate('cascade');

            $table->bigInteger('objid')->unsigned();

            $table->biginteger('orgid')->unsigned()->nullable()->index()->comment('Кто понес затраты');
		$table->foreign('orgid')->references('id')->on('orgs');
            $table->biginteger('opertypeid')->unsigned()->nullable()->comment('Тип деятельности');
		$table->foreign('opertypeid')->references('id')->on('opertypes');

            $table->bigInteger('expensetypeid')->nullable()->unsigned()->comment('id вида затрат по спр-ку')->index();
		$table->foreign('expensetypeid')->references('id')->on('expensetypes');

//            $table->bigInteger('categoryid')->unsigned()->nullable();
//		$table->foreign('categoryid')->references('id')->on('pay_categories')->comment('Вид платежа');

            $table->date('operdate')->default(date("Y-m-d"))->comment('Дата учета операции');

	    $table->decimal('qty', 9,3)->nullable()->comment('Кол-во, ЕИ');
	    $table->decimal('price', 12,2)->nullable()->comment('Цена 1 ЕИ');
            $table->decimal('expense_sum',12,2)->nullable()->comment('Сумма затрат, руб');
            $table->string('reason',160)->nullable()->comment('Причина, описание затрат');

            $table->boolean('active')->default(1)
                ->comment('1-активно, 0-черновик');

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');

	//	$table->unique(['sysobjid', 'code'], 'stages_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('obj_expenses');
    }
}
