<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractRegnumsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_regnums', function (Blueprint $table) {
            $table->id();

            $table->biginteger('orgid')->unsigned()->comment('');
            $table->biginteger('categoryid')->unsigned()->comment('Доходный/Расходный/Кредит');
		$table->foreign('categoryid')->references('id')->on('contract_categories')->comment('Категория договора')
		->onDelete('cascade');
            $table->integer('regnum')->unsigned()->default(1)->comment('Свободный Регистрационный номер');

            $table->timestamps();

            $table->index(['orgid','categoryid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contract_regnums');
    }
}
