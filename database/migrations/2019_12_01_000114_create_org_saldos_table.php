<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrgSaldosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('org_saldos', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('orgid')->unsigned()->comment('Контрагент по orgs.ID');
            $table->bigInteger('ownorgid')->unsigned()->comment('Продавец по orgs.ID');

            $table->date('ondate')->comment('По данным на 00:00 этой даты');
            $table->decimal('saldo',12,2)
		->comment('Сальдо с клиентом. Если меньше нуля, то контрагент должен Продавцу');

            $table->boolean('active')->default(true);

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');

	
	$table->index(['ownorgid','orgid','ondate']);

            $table->foreign('orgid')->references('id')->on('orgs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('ownorgid')->references('id')->on('orgs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('org_saldos');
    }
}
