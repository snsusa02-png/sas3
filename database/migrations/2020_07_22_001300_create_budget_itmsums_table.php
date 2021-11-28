<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBudgetItmsumsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('budget_itmsums', function (Blueprint $table) {
            $table->id();

            $table->biginteger('parid')->unsigned()->nullable()->index()->comment('Родительская запись в budget_itmsums');
		$table->foreign('parid')->references('id')->on('budget_itmsums');

            $table->biginteger('budgetid')->unsigned()->index()->comment('Бюджет');
		$table->foreign('budgetid')->references('id')->on('budgets');

            $table->biginteger('itmid')->unsigned()->index()->comment('Вид работ');
		$table->foreign('itmid')->references('id')->on('budget_items');

 	   $table->biginteger('acnttypeid')->unsigned()->nullable()->index()->comment('тип статьи расхода/дохода');
		$table->foreign('acnttypeid')->references('id')->on('bdgtacnttypes');

           $table->string('descript',160)->comment('Пояснение, примечание')->nullable();

           $table->string('plnsum_calc',36)->comment('Формула расчета плановой суммы')->nullable();
 	   $table->decimal('plnsum',12,2)->nullable()->comment('Плановая сумма по виду работ/статье. Прямое редактирование');

 	   $table->decimal('fctsum',12,2)->nullable()->comment('Фактическая сумма по виду работ/статье. Редактирование через операции');
 	   $table->decimal('fctinpsum',12,2)->nullable()->comment('Фактическая сумма всех приходов по виду работ/статье. Редактирование через операции');
  	   $table->decimal('fctoutsum',12,2)->nullable()->comment('Фактическая сумма всех расходов по виду работ/статье. Редактирование через операции');

            $table->boolean('for_m15')->nullable()->default(false);
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
        Schema::dropIfExists('budget_itmsums');
    }
}
