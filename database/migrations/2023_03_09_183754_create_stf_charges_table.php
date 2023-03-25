<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStfChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stf_charges', function (Blueprint $table) {
            $table->id();

            $table->biginteger('staffid')->unsigned()->nullable()->comment('Организация');
		$table->foreign('staffid')->references('id')->on('orgstaff');

            $table->biginteger('orgchargeid')->unsigned()->comment('Вид начисления/удержания организации');
		$table->foreign('orgchargeid')->references('id')->on('org_charges');

            $table->date('begdate')->comment('начало действия');
            $table->date('enddate')->comment('конец действия')->nullable();

            $table->decimal('charge_sum',12,2)->nullable()->comment('сумма удержания/начисления');

            $table->string('notes',160)->nullable()->comment('Примечание');

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
        Schema::dropIfExists('stf_charges');
    }
}
