<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pay_categories', function (Blueprint $table) {
            $table->id();

//            $table->bigInteger('parid')->nullable()->unsigned()->comment('id родит. категории');
//		$table->foreign('parid')->references('id')->on('proj_categories')
//		->onDelete('cascade')->onUpdate('cascade');

            $table->string('name',120);
            $table->string('descript',300)->nullable();

            $table->integer('ordr')->unsigned()->default(999999);
            $table->boolean('invoice_drctpay')->default(true)->comment('0-запрет прямой передачи счета на оплату');
            $table->boolean('active')->default(true);

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
        Schema::dropIfExists('pay_categories');
    }
}
