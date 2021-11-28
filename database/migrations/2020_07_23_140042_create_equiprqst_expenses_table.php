<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquiprqstExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('equiprqst_expenses', function (Blueprint $table) {
            $table->id();
            $table->biginteger('rqstid')->unsigned()->index();
		$table->foreign('rqstid')->references('id')->on('equiprqsts')->onDelete('cascade');

            $table->decimal('expense_sum',12,2)->nullable()->comment('Сумма затрат, руб');
            $table->string('reason',160)->nullable()->comment('Причина затрат');

            $table->bigInteger('expensetypeid')->nullable()->unsigned()->comment('id вида затрат по спр-ку')->index();
//		$table->foreign('expensetypeid')->references('id')->on('expensetypes');

 	   $table->biginteger('bdgtitmsumid')->unsigned()->nullable()->index()->comment('источник финансирования');
		$table->foreign('bdgtitmsumid')->references('id')->on('budget_itmsums');

            $table->biginteger('exeorgid')->unsigned()->nullable()->index()->comment('Исполнитель');
		$table->foreign('exeorgid')->references('id')->on('orgs');

            $table->biginteger('invoiceid')->unsigned()->nullable()->index()->comment('По счету');
		$table->foreign('invoiceid')->references('id')->on('invoices')->onDelete('cascade');

            $table->biginteger('upd_id')->unsigned()->nullable()->index()->comment('По с/фактуре');
		$table->foreign('upd_id')->references('id')->on('invoices')->onDelete('cascade');

            $table->date('operdate')->nullable()->comment('Дата операции / Дата учета');

            $table->string('notes',360)->nullable()->comment('Примечание');

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
        Schema::dropIfExists('equiprqst_expenses');
    }
}
