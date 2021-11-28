<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCwpWorkEquipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cwp_work_equips', function (Blueprint $table) {
            $table->id();

            $table->biginteger('workid')->unsigned()->index();
		$table->foreign('workid')->references('id')->on('cwp_works')->onDelete('cascade');

            $table->biginteger('ri_limid')->unsigned()->nullable()->index();
		$table->foreign('ri_limid')->references('id')->on('bot_ri_lims')->onDelete('cascade');

            $table->biginteger('refitmid')->unsigned()->index();
		$table->foreign('refitmid')->references('id')->on('refitems');

            $table->bigInteger('unittypeid')->unsigned()->nullable()->comment('ID единицы измерения (по UnitTypes.id)');
            $table->decimal('qty',10,3)->comment('Необходимое кол-во, в ЕИ');


            $table->decimal('sup_beg_shift',6,1)->nullable()->default(0)->comment('Смещение (в часах) поставки от начала cwp_works');
		// или
            $table->decimal('sup_end_shift',6,1)->nullable()->comment('Смещение (в часах) поставки от окончания cwp_works');


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
        Schema::dropIfExists('cwp_work_equips');
    }
}
