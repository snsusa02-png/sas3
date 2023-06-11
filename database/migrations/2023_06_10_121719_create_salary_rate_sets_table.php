<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalaryRateSetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salary_rate_sets', function (Blueprint $table) {
            $table->id();

            $table->string('name',160)->comment('Название группы ставок');

            $table->biginteger('ownorgid')->unsigned()->nullable()->comment('Для какой организации. Null - подходит всем');
		$table->foreign('ownorgid')->references('id')->on('orgs');

            $table->biginteger('payrolltypeid')->unsigned()->comment('Тип начисления ЗП');
		$table->foreign('payrolltypeid')->references('id')->on('payrolltypes');

            $table->date('begdate')->comment('начало действия');
            $table->date('enddate')->nullable()->comment('конец действия');

            $table->string('notes',300)->nullable()->comment('Примечание');

            $table->boolean('active')->default(true);

            /*
	    $table->timestamp('approved_at')->nullable();
            $table->bigInteger('approved_by')->nullable()->unsigned()
		->Comment('Кто согласовал ставку/период действия');

            $table->boolean('locked')->default(false);
	   */

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
        Schema::dropIfExists('salary_rate_sets');
    }
}
