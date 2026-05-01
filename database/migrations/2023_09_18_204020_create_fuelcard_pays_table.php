<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFuelcardPaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fuelcard_pays', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('cardid')->unsigned()->comment('Карта');
		$table->foreign('cardid')->references('id')->on('fuelcards');

            $table->bigInteger('machineid')->unsigned()->nullable()->index('machineid');
		$table->foreign('machineid')->references('id')->on('machines');

            $table->biginteger('driverid')->unsigned()->nullable()->comment('Сотрудник');
		$table->foreign('driverid')->references('id')->on('orgstaff');

            $table->biginteger('ri_sup_priceid')->unsigned()->nullable()->comment('Предложение поставщика');
		$table->foreign('ri_sup_priceid')->references('id')->on('ri_sup_prices');

            $table->biginteger('refitmid')->unsigned()->nullable()->comment('Вид топлива (refitems.id)');
		$table->foreign('refitmid')->references('id')->on('refitems');

            $table->tinyInteger('paydir')->default(0)->comment('Направление платежа (от ownorgid) +1 - Пополнение, -1 - Расход/Списание');
            $table->date('paydate')->default(date("Y-m-d"))->comment('Дата платежа');
	    $table->decimal('paysum', 12,2)->nullable()->comment('Сумма платежа, руб');
	    $table->decimal('fuel_qty', 12,2)->nullable()->comment('Кол-во топлива, ЕИ');
	    $table->decimal('fuel_price', 12,6)->nullable()->comment('Цена за ЕИ, руб');

            $table->string('notes',160)->nullable();

            $table->boolean('active')->default(1)
                ->comment('1-признак активности, 0 - черновик');

            $table->timestamp('created_at')->nullable()->useCurrent = true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
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
        Schema::dropIfExists('fuelcard_pays');
    }
}
