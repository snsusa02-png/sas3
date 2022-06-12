<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWrhStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Товарный запас склада
        Schema::create('wrh_stocks', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('ownorgid')->unsigned()->comment('владелец запаса');
            $table->bigInteger('wrhid')->unsigned()->index('wrhid');
            $table->bigInteger('boxid')->unsigned()->index('boxid');

            $table->bigInteger('sysobjid')->unsigned()->nullable();
            $table->bigInteger('objid')->unsigned()->nullable();
            $table->tinyInteger('grpid')->unsigned()->nullable()
                ->comment('ID "кучки". 1-исходный товар, 3-переработанный(подготовленный) товар');

//            $table->bigInteger('ordid')->unsigned()->nullable()->index('ordid')
//		->comment('если не пусто, то этот товар зарезервирован для указанного заказа');

            $table->bigInteger('refitmid')->unsigned()->index('refitmid');

            $table->decimal('qty',12,3)->default(0)->comment('доступный остаток на складе');

            $table->decimal('plnincqty',12,3)->default(0)->comment('потенцальный приход (по непроведенным документам)');
            $table->decimal('plnoutqty',12,3)->default(0)->comment('потенцальный расход (по непроведенным документам)');

            $table->timestamp('info_dt')->comment('По состоянию на момент времени')->useCurrent=true;

            $table->timestamp('created_at')->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');


            $table->index(['wrhid', 'sysobjid', 'objid']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wrh_stocks');
    }
}
