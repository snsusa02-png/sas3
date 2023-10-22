<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStfChrgCalcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stf_chrg_calcs', function (Blueprint $table) {
            $table->id();

            $table->biginteger('staffid')->unsigned()->nullable()->comment('Сотрудник');
		$table->foreign('staffid')->references('id')->on('orgstaff');

            $table->biginteger('orgchargeid')->unsigned()->nullable()->comment('Вид начисления/удержания организации');
		$table->foreign('orgchargeid')->references('id')->on('org_charges');

            $table->integer('charge_dir')->comment('-1 - удержание, +1 - начисление');
            $table->decimal('charge_sum',12,2)->nullable()->comment('сумма удержания/начисления');

            $table->date('docdate')->comment('Дата документа');
            $table->string('docnum',16)->nullable()->comment('Номер документа');
            $table->date('forbegdate')->comment('начислено за период, начало');
            $table->date('forenddate')->comment('начислено за период, конец');

            $table->string('notes',300)->nullable()->comment('Примечание');

            $table->boolean('active')->default(true);

	    $table->unsignedBigInteger('ref_sysobjid')->nullable();
		      $table->foreign('ref_sysobjid')->references('id')->on('sysobjs')
			->onDelete('cascade');
	    $table->unsignedBigInteger('ref_objid')->nullable();

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
        Schema::dropIfExists('stf_chrg_calcs');
    }
}
