<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiOrgPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ri_org_prices', function (Blueprint $table) {
            $table->id();


	    $table->bigInteger('refitmid')->unsigned()->index('refitmid');
	            $table->foreign('refitmid')->references('id')->on('refitems');

            $table->biginteger('orgid')->unsigned()->index()->comment('Чья цена');
	            $table->foreign('orgid')->references('id')->on('orgs');

            $table->biginteger('placeid')->unsigned()->index()->comment('В каком месте');
	            $table->foreign('placeid')->references('id')->on('org_places');

            $table->date('begdate')->useCurrent=true;
            $table->date('enddate')->nullable();

            $table->decimal('price',12,2)->nullable()->comment('Цена за 1ЕИ (refitems.unittypeid)');

            $table->string('notes',90)->nullable();

            $table->tinyinteger('active')->default(1)->comment('0-черновик,1-активно');


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
        Schema::dropIfExists('ri_org_prices');
    }
}
