<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjFinopersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obj_finopers', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('sysobjid')->unsigned();
		$table->foreign('sysobjid')->references('id')->on('sysobjs');

            $table->bigInteger('objid')->unsigned();


            $table->bigInteger('srcorgid')->unsigned()->comment('ID контрагента-источника (товара/денег)')->index();
            $table->bigInteger('tgtorgid')->unsigned()->comment('ID контрагента-получателя (товара/денег)')->index();
            $table->bigInteger('contractid')->unsigned()->comment('ID договора')->index();

            $table->date('operdate')->comment('Дата операции');
            $table->decimal('qty', 8,2)->nullable()->comment('кол-во');
            $table->decimal('price', 12,2)->nullable()->comment('цена, руб');
            $table->decimal('opersum', 12,2)->comment('Передаваемая сумма');

            $table->tinyInteger('sumtypeid')->comment('1-деньги; 2-товар/услуга');

            $table->string('mark', 16)->nullable()->comment('Что-то вроде уникального кода в пределах sysobjid/objid - для update');

            $table->string('descript', 60)->nullable()->comment('Описание (Детали) операции');

            //$table->boolean('active')->default(1)->comment('0-черновик; 1-отображать');


            $table->timestamp('created_at')->nullable()->useCurrent = true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');

            $table->index(['sysobjid','objid']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('obj_finopers');
    }
}
