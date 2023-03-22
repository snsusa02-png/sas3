<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStfPaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stf_pays', function (Blueprint $table) {
            $table->id();

            $table->biginteger('staffid')->unsigned()->comment('Сотрудник');
		$table->foreign('staffid')->references('id')->on('orgstaff');

            $table->integer('pay_dir')->comment('-1 - выплачено сотруднику, +1 - принято от сотрудника');
            $table->decimal('pay_sum',12,2)->nullable()->comment('сумма удержания/начисления');
            $table->string('pay_reason',160)->nullable()->comment('Основание платежа');

            $table->date('paydate')->comment('Дата проведения платежа');
            $table->biginteger('pay_staffid')->unsigned()->comment('Сотрудник, проводивший платеж');
		$table->foreign('pay_staffid')->references('id')->on('orgstaff');

            $table->date('docdate')->nullable()->comment('Дата документа');
            $table->string('docnum',16)->nullable()->comment('Номер документа');

            $table->biginteger('chargetypeid')->unsigned()->nullable()->comment('Связано с начислением/удержанием определенного типа');
		$table->foreign('chargetypeid')->references('id')->on('chargetypes');

            $table->string('notes',300)->nullable()->comment('Примечание');

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
        Schema::dropIfExists('stf_pays');
    }
}
