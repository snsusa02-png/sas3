<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBudgetOpersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('budget_opers', function (Blueprint $table) {
            $table->id();

            $table->biginteger('itmsumid')->unsigned()->index()->comment('');
		$table->foreign('itmsumid')->references('id')->on('budget_itmsums');

            $table->biginteger('lnk_operid')->unsigned()->nullable()->index()->comment('');
//		$table->foreign('lnk_operid')->references('id')->on('budget_opers')->onDelete('cascade');

            $table->date('operdate')->comment('Дата операции');
 	    $table->tinyinteger('dir')->default(0)->comment('+1 - приход, -1 - расход');
 	    $table->decimal('opersum',12,2)->nullable()->comment('сумма операции');

            $table->string('reason',160)->nullable()->comment('Основание операции');
            $table->biginteger('rsn_sysobjid')->unsigned()->nullable()->comment('');
		$table->foreign('rsn_sysobjid')->references('id')->on('sysobjs');
            $table->biginteger('rsn_objid')->unsigned()->nullable()->comment('');

            $table->boolean('stable')->default(true);
            $table->boolean('active')->default(true);

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');


		$table->index(['rsn_sysobjid','rsn_objid']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('budget_opers');
    }
}
