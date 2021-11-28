<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEritmSuppliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('eritm_supplies', function (Blueprint $table) {
            $table->id();

            $table->biginteger('eritmid')->unsigned()->index();
		$table->foreign('eritmid')->references('id')->on('equiprqst_items')->onDelete('cascade');

            $table->biginteger('offerid')->unsigned()->index();
		$table->foreign('offerid')->references('id')->on('eritm_offers')->onDelete('cascade');

            $table->bigInteger('invoiceid')->nullable()->unsigned()->comment('УПД поставщика по системе Invoices')->index();
//		$table->foreign('invoiceid')->references('id')->on('invoices');

            $table->date('fctgetdate')->nullable()->comment('Дата получения товара');
            $table->string('unit',16)->nullable()->comment('! не нужна, есть в eritm_offers - Единица измерения');

            $table->decimal('doc_qty',10,3)->nullable()->default(0)->comment('Кол-во по документу поставщика в ЕИ поставщика');
            $table->decimal('get_qty',10,3)->nullable()->default(0)->comment('Кол-во по документу поставщика в ЕИ заявки');

            $table->decimal('m15_qty',10,3)->nullable()->default(0)->comment('Передано заказчику (orgid) по формам М15, ЕИ поставщика');

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
        Schema::dropIfExists('eritm_supplies');
    }
}
