<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWrhdoclstTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wrhdoclst', function (Blueprint $table) {
            $table->bigIncrements('id');


            $table->bigInteger('docid')->unsigned()->index('docid');
            $table->bigInteger('subtypeid')->unsigned()->nullable()->index('subtypeid');

            $table->biginteger('prelstid')
		   ->unsigned()
	           ->nullable()
                   ->comment('Id предшествующей записи из документа-прародителя (wrhdocs.predocid)');

            $table->bigInteger('oiid')->unsigned()
	           ->nullable()
		->comment('связка со строкой заказа, которую обеспечивает данная строка складского документа');

            $table->bigInteger('refitmid')->unsigned()->index('refitmid');

            $table->DECIMAL('qty',12,3)->default(0)->comment('Кол-во товара, еи');
            $table->DECIMAL('price',12,2)->nullable()->comment('Цена за единицу товара, руб');
            $table->DECIMAL('sum',12,2)->nullable()->comment('сумма за указанное кол-во товара, руб');

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->comment('UserID сотрудника, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->comment('UserID сотрудника, изменившего запись');

            //FK
            $table->foreign('docid')->references('id')->on('wrhdocs');
            //$table->foreign('refitmid')->references('id')->on('refitems');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wrhdoclst');
    }
}
