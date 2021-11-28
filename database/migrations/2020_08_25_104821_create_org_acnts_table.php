<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrgAcntsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('org_acnts', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('orgid')->nullable()->unsigned();
		$table->foreign('orgid')->references('id')->on('orgs')->comment('Владелец');

            $table->biginteger('bankid')->unsigned()->nullable()->index()->comment('Банк');
//		$table->foreign('bankid')->references('id')->on('banks');

            $table->string('bankname',160)->nullable()->comment('Название банка');
            $table->string('bankaddr',160)->nullable()->comment('Адрес банка');
            $table->string('bic',9)->nullable()->comment('БИК');
            $table->string('rs_num',20)->nullable()->comment('Расчетный счет');
            $table->string('cs_num',20)->nullable()->comment('Корреспондентский счет');

            $table->decimal('rest_sum',12,2)->nullable()->comment('Остаток на р/счете на дату');
            $table->date('rest_date')->nullable()->comment('Дата остатка');

            $table->boolean('forpay')->default(true)->comment('1-Можно исп-ть для оплаты счетов');
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
        Schema::dropIfExists('org_acnts');
    }
}
