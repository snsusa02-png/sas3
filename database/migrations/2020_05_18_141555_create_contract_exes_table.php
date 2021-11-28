<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractExesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_exes', function (Blueprint $table) {
            $table->id();

	    $table->biginteger('contractid')->unsigned()->index();
		$table->foreign('contractid')->references('id')->on('contracts');
            
	    $table->biginteger('buildobjid')->unsigned()->nullable()->index()->comment('ID объекта');
//		$table->foreign('buildobjid')->references('id')->on('buildobjs');

	    $table->biginteger('buildopertypeid')->unsigned()->nullable()->index()->comment('ID вида работ');
//		$table->foreign('buildopertypeid')->references('id')->on('buildopertypes');

	    $table->unsignedTinyInteger('doctypeid')->nullable()->comment('1-КС2, 2-Материалы');

        $table->date('docdate')->comment('дата документа');
        $table->decimal('docsum',12,2)->comment('сумма документа');
	
        $table->string('matsum_calc',36)->comment('Формула расчета суммы материалов')->nullable();
        $table->decimal('mat_sum',12,2)->comment('сумма материалов')->nullable();

        $table->string('mehsum_calc',36)->comment('Формула расчета суммы ')->nullable();
        $table->decimal('meh_sum',12,2)->comment('сумма машины и механизмы')->nullable();

        $table->string('fotsum_calc',36)->comment('Формула расчета суммы ')->nullable();
        $table->decimal('fot_sum',12,2)->comment('сумма ФОТ')->nullable();

        $table->string('nrsum_calc',36)->comment('Формула расчета суммы ')->nullable();
        $table->decimal('nr_sum',12,2)->comment('сумма накладных расходов')->nullable();

        $table->string('spsum_calc',36)->comment('Формула расчета суммы ')->nullable();
        $table->decimal('sp_sum',12,2)->comment('сумма сметной прибыли')->nullable();

        $table->string('m15sum_calc',36)->comment('Формула расчета суммы ')->nullable();
        $table->decimal('m15_sum',12,2)->comment('сумма давальческих материалов')->nullable();

	    $table->biginteger('rsn_sysobjid')->unsigned()->nullable()->comment('sysobjid записи-основания');
		$table->foreign('rsn_sysobjid')->references('id')->on('sysobjs');
            $table->biginteger('rsn_objid')->unsigned()->nullable()->comment('');


        $table->string('docinfo',60)->nullable()->comment('Описание документа-основания');

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
        Schema::dropIfExists('contract_exes');
    }
}
