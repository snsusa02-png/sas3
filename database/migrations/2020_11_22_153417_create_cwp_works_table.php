<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCwpWorksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cwp_works', function (Blueprint $table) {
            $table->id();

	    $table->biginteger('cwp_id')->unsigned()->index()->nullable();

//	    $table->biginteger('contractid')->unsigned()->index()->nullable();
//		$table->foreign('contractid')->references('id')->on('contracts');
            
//            $table->biginteger('buildopertypeid')->unsigned()->index();
//		$table->foreign('buildopertypeid')->references('id')->on('buildopertypes')->onDelete('cascade');

//            $table->biginteger('bdgtitmid')->unsigned()->index()->nullable();
//		$table->foreign('bdgtitmid')->references('id')->on('budget_items');

	    $table->biginteger('keyworkid')->unsigned()->index();
		$table->foreign('keyworkid')->references('id')->on('bot_keyworks');

            $table->string('name',160)->comment('Название вида работ');

            $table->decimal('plnqty',12,3)->nullable()->comment('Требуемое кол-во, ЕИ');
            $table->biginteger('unittypeid')->unsigned()->index()->nullable();
		$table->foreign('unittypeid')->references('id')->on('unittypes');

            $table->decimal('doneqty',12,3)->nullable()->comment('Выполненное кол-во, ЕИ');
            $table->decimal('ks2qty',12,3)->nullable()->comment('Передано по КС-2, ЕИ');

            $table->datetime('plnbegdt')->nullable()->comment('Планируемая дата/время начала работы');
            $table->datetime('estbegdt')->nullable()->comment('Расчетная дата/время начала работы');
            $table->datetime('fctbegdt')->nullable()->comment('Фактическая дата/время начала работы');

            $table->tinyinteger('begsetmode')->nullable()->default(1)->comment('1-явно, 2-как смещение от начала другой работы, 3-как смещение от окончания другой работы');

            $table->decimal('plnworkdays',10,1)->nullable()->default(1)->comment('Планируемая продолжительность работы, дней');
            //$table->decimal('plnworkhrs',10,1)->nullable()->default(1)->comment('Планируемая продолжительность работы, часов');

            $table->decimal('estworkhrs',10,1)->nullable()->comment('Расчетная продолжительность работы с учетом фактичесокого темпа работ, часов');
            $table->decimal('fctworkhrs',10,1)->nullable()->default(0)->comment('Фактическая продолжительность работы, часов');

            $table->datetime('plnenddt')->nullable()->comment('Планируемая дата/время окончания работы');
            $table->datetime('estenddt')->nullable()->comment('Расчетная дата/время окончания работы');
            $table->datetime('fctenddt')->nullable()->comment('Фактическая дата/время окончания работы');

            $table->string('notes',160)->nullable()->comment('Примечание');

            $table->integer('ordr')->default(1);


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
        Schema::dropIfExists('cwp_works');
    }
}
