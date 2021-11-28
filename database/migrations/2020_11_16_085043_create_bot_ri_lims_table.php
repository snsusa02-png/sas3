<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotRiLimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot_ri_lims', function (Blueprint $table) {
            $table->id();

 	   $table->biginteger('bdgtitmsumid')->unsigned()->nullable()->index()->comment('источник финансирования');
		$table->foreign('bdgtitmsumid')->references('id')->on('budget_itmsums');

            $table->biginteger('buildopertypeid')->unsigned()->index();
		$table->foreign('buildopertypeid')->references('id')->on('buildopertypes')->onDelete('cascade');

            $table->bigInteger('refitmid')->nullable()->unsigned()->comment('id по спр-ку refitmes')->index();
		$table->foreign('refitmid')->references('id')->on('refitems');

            $table->bigInteger('unittypeid')->unsigned()->nullable()->comment('ID единицы измерения (по UnitTypes.id)');
		$table->foreign('unittypeid')->references('id')->on('unittypes');

            $table->decimal('lim_qty',10,3)->nullable()->default(0)->comment('Предельное кол-во, ЕИ');

            $table->string('smet_price_calc',36)->comment('Формула расчета цены сметы')->nullable();
            $table->decimal('smet_price',12,4)->nullable()->comment('Цена по смете, руб');
            $table->decimal('smet_sum',12,2)->nullable()->comment('Сумма по смете, руб');

            $table->decimal('rqst_qty',10,3)->nullable()->default(0)->comment('Заказанное кол-во, ЕИ');
            $table->decimal('get_qty',10,3)->nullable()->default(0)->comment('Полученное кол-во, ЕИ');

            $table->string('notes',160)->nullable()->comment('Примечание');

//            $table->integer('ordr')->default(1);


//            $table->decimal('est_price',12,2)->nullable()->comment('Оценочная цена, руб');


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
        Schema::dropIfExists('bot_ri_lims');
    }
}
