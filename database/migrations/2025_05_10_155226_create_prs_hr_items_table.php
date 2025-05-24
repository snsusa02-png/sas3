<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrsHrItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prs_hr_items', function (Blueprint $table) {
            $table->id();
            $table->biginteger('prs_id')->unsigned()->comment('ID набора ставок');
		$table->foreign('prs_id')->references('id')->on('prize_rate_sets');

          /*  $table->biginteger('wrktypeid')->unsigned()->comment('Тип работы');
	            $table->foreign('wrktypeid')->references('id')->on('wrktypes');*/

            $table->decimal('min_wrkhrs',4,1)->comment('Минимальное кол-во отработанных часов за месяц (>=)');
            $table->decimal('max_wrkhrs',4,1)->nullable()->comment('Максимальное кол-во отработанных часов за месяц (<)');

            $table->decimal('hr_rate',10,2)->nullable()->comment('Ставка за час работы, руб');

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
        Schema::dropIfExists('prs_hr_items');
    }
}
