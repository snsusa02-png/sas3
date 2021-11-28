<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	//Рассмотрение/Согласование договора
        Schema::create('contract_reviews', function (Blueprint $table) {
            $table->id();
            $table->biginteger('contractid')->unsigned()->index();
		$table->foreign('contractid')->references('id')->on('contracts');

            $table->biginteger('inituserid')->unsigned()->index();
		$table->foreign('inituserid')->references('id')->on('users');

            $table->string('descript',360)->nullable()->comment('Описание цели согласования');

            $table->datetime('plnbegdt')->nullable()->comment('Начало рассмотрения');
            $table->datetime('plnenddt')->nullable()->comment('Окончить рассмотрение до');

            $table->datetime('fctbegdt')->nullable()->comment('Факт. начало рассмотрения');
            $table->datetime('fctenddt')->nullable()->comment('Факт. окончание рассмотрения');


            $table->tinyInteger('statusid')->nullable()->unsigned()->default(0)
		->comment('0-черновик; 1-в процессе; 2-завершено');

//            $table->boolean('active')->default(true);

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
        Schema::dropIfExists('contract_reviews');
    }
}
