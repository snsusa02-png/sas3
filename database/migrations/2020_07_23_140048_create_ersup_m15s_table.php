<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateErsupM15sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	// Связь поставки с формой передачи подрядчику по накладным (м-15)

        Schema::create('ersup_m15s', function (Blueprint $table) {
            $table->id();

            $table->biginteger('ersupid')->unsigned()->index();
		$table->foreign('ersupid')->references('id')->on('eritm_supplies')->onDelete('cascade');

            $table->bigInteger('m15docid')->unsigned()->comment('id документа М15 передачи заказчику по спр-ку m15docs')->index();
//		$table->foreign('m15docid')->references('id')->on('m15docs');

            $table->decimal('m15_qty',10,3)->nullable()->default(0)->comment('Кол-во по документу М15 в ЕИ');

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
        Schema::dropIfExists('ersup_m15s');
    }
}
