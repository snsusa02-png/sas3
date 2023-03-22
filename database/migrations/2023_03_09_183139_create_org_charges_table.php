<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrgChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('org_charges', function (Blueprint $table) {
            $table->id();

            $table->biginteger('orgid')->unsigned()->nullable()->comment('Организация');
		$table->foreign('orgid')->references('id')->on('orgs');

            $table->biginteger('chargetypeid')->unsigned()->nullable()->comment('Тип начисления/удержания');
		$table->foreign('chargetypeid')->references('id')->on('chargetypes');

            $table->date('begdate')->comment('начало действия');
            $table->date('enddate')->nullable()->comment('конец действия');

            $table->decimal('charge_sum',12,2)->nullable()->comment('сумма удержания/начисления');
            $table->string('charge_period',1)->nullable()->default('1')->comment('1 - единоразово. За период: D-день, W-неделя, M-месяц, Q-квартал, Y-год');

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
        Schema::dropIfExists('org_charges');
    }
}
