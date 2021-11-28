<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBudgetItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();

            $table->biginteger('budgetid')->unsigned()->index()->comment('Бюджет');
		$table->foreign('budgetid')->references('id')->on('budgets');

            $table->biginteger('parid')->unsigned()->nullable()->index()->comment('Родительская запись');
//		$table->foreign('parid')->references('id')->on('budget_items');

            $table->biginteger('refitmid')->unsigned()->nullable()->index()->comment('Связанная запись другого бюджета');
		$table->foreign('refitmid')->references('id')->on('budget_items');
	
            $table->biginteger('buildopertypeid')->unsigned()->nullable()->index()->comment('Вид работ объекта');
		$table->foreign('buildopertypeid')->references('id')->on('buildopertypes');

            $table->bigInteger('contractid')->nullable()->unsigned();
		$table->foreign('contractid')->references('id')->on('contracts')->comment('договор - основание для данного раздела бюджета');

           $table->string('name',160)->comment('Наименование раздела бюджета, наследуется от вида работ');

 	   $table->decimal('estdocsum',12,2)->nullable()->comment('сметная стоимость / лимит / цель');

// 	   $table->biginteger('acnttypeid')->unsigned()->nullable()->index()->comment('тип статьи расхода/дохода');

 	   $table->decimal('itmsum',12,2)->nullable()->comment('текущая сумма (остаток) на статье');

 	   $table->decimal('par_trsnfrate',5,1)->nullable()->comment('% коэфф-т передачи сумм от родительской записи');
 	   $table->decimal('par_corrpcnt',5,1)->nullable()->comment('договорной понижающий/повышающий коэфф-т');


            $table->boolean('active')->default(true);
            $table->integer('ordr')->unsigned()->nullable()->default(0)->comment('примерный порядок');

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
        Schema::dropIfExists('budget_items');
    }
}
