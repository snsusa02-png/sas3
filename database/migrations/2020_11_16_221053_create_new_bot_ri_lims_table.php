<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewBotRiLimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('new_bot_ri_lims', function (Blueprint $table) {
            $table->id();

            $table->string('code',30)->nullable()->comment('Код');
            $table->string('name',300)->comment('Наименование');
            $table->string('unit',16)->nullable()->comment('ЕИ');

            $table->decimal('qty',14,6)->nullable()->comment('Предельное кол-во, ЕИ');

            $table->string('smet_price_calc',36)->comment('Формула расчета цены сметы')->nullable();
            $table->decimal('smet_price',12,4)->nullable()->comment('Цена по смете, руб');
            $table->decimal('smet_sum',12,2)->nullable()->comment('Сумма по смете, руб');


            $table->bigInteger('unittypeid')->unsigned()->nullable()->comment('ID единицы измерения (по UnitTypes.id)');
		$table->foreign('unittypeid')->references('id')->on('unittypes');

            $table->bigInteger('refitmid')->nullable()->unsigned()->comment('id по спр-ку refitmes')->index();
//		$table->foreign('refitmid')->references('id')->on('refitems');

            $table->bigInteger('bot_ri_lims_id')->unsigned()->nullable()->comment('ID записи в ресурсной ведомости. Признак полной передачи');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('new_bot_ri_lims');
    }
}
