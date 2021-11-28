<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_prices', function (Blueprint $table) {
            
		$table->id();
	        $table->biginteger('contractid')->unsigned()->index();
            
		$table->biginteger('sysobjid')->unsigned()->nullable()->index()->comment('ID типа объекта (по sysobjs)');
		$table->biginteger('objid')->unsigned()->nullable()->index()->comment('ID объекта');

		$table->biginteger('unitid')->unsigned()->nullable();
	        $table->string('unitcode',16)->comment('ЕИ')->default('час');

	        $table->decimal('price',12,2)->comment('');
	
 	    	$table->string('descript',120)->nullable();

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
        Schema::dropIfExists('contract_prices');
    }
}
