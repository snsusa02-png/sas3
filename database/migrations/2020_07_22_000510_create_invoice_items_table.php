<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoiceItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

 	   $table->biginteger('invoiceid')->unsigned()->nullable()->index()->comment('связь с документом');
		$table->foreign('invoiceid')->references('id')->on('invoices');

            $table->biginteger('eritmid')->unsigned()->index();
//		$table->foreign('eritmid')->references('id')->on('equiprqst_items')->onDelete('set null');

            $table->string('code', 30)->nullable()->comment('Код товара по системе поставщика');
            $table->string('itmname',300)->comment('Наименование товара/услуги');

            $table->string('unit', 16)->nullable()->default('шт')->comment('Единица измерения');
            $table->bigInteger('unittypeid')->unsigned()->nullable()->comment('ID единицы измерения (по UnitTypes.id)');
		$table->foreign('unittypeid')->references('id')->on('unittypes');

            $table->bigInteger('refitmid')->nullable()->unsigned()->comment('id по спр-ку refitmes')->index();
		$table->foreign('refitmid')->references('id')->on('refitems');

            $table->decimal('qty',10,3)->nullable()->default(0)->comment('Кол-во, ЕИ');

            $table->decimal('price',12,4)->nullable()->comment('Цена, руб');
            $table->decimal('itmsum',12,2)->nullable()->comment('Сумма, руб');

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
        Schema::dropIfExists('invoice_items');
    }
}
