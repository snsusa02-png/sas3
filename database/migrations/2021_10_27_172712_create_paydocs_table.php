<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaydocsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paydocs', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('ownorgid')->unsigned()->index('ownorgid')
                ->comment('ID организации-владельца (по orgs.ID)');

            $table->bigInteger('orgid')->unsigned()->index('orgid')
                ->comment('ID организации-контрагента (по orgs.ID)');

	    $table->biginteger('contractid')->unsigned()->index();
		$table->foreign('contractid')->references('id')->on('contracts');

            $table->biginteger('opertypeid')->unsigned()->nullable()->comment('Вид Работ');
		$table->foreign('opertypeid')->references('id')->on('opertypes');

	    $table->biginteger('rsn_sysobjid')->unsigned()->nullable()->comment('sysobjid записи-основания');
		$table->foreign('rsn_sysobjid')->references('id')->on('sysobjs');
            $table->biginteger('rsn_objid')->unsigned()->nullable()->comment('');

            $table->string('docnum', 16)->nullable()->comment('Номер документа');
            $table->date('doсdate')->nullable()->comment('Дата документа');
            $table->bigInteger('paytypeid')->unsigned()
                ->comment('ID типа платежа (по paytypes.ID)');
            $table->string('reason', 160)->nullable()->comment('Основание платежа');

            $table->date('paydate')->default(date("Y-m-d"))->comment('Дата платежа');
            $table->tinyInteger('paydir')->default(0)->comment('Направление платежа (от ownorgid)');
	    $table->decimal('paysum', 12,2)->nullable()->comment('Сумма платежа');

            $table->boolean('active')->default(1)
                ->comment('1-признак активности, 0 - черновик');

            $table->timestamp('created_at')->nullable()->useCurrent = true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');

 		$table->unique(['rsn_sysobjid', 'rsn_objid'], 'paydocs_rsn_indx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paydocs');
    }
}
