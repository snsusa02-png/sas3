<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractWorkplansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_workplans', function (Blueprint $table) {
            $table->id();

	    $table->biginteger('contractid')->unsigned()->index();
		$table->foreign('contractid')->references('id')->on('contracts');
            
            $table->biginteger('buildopertypeid')->unsigned()->index();
//		$table->foreign('buildopertypeid')->references('id')->on('buildopertypes')->onDelete('cascade');

//            $table->biginteger('bdgtitmid')->unsigned()->index()->nullable();
//		$table->foreign('bdgtitmid')->references('id')->on('budget_items');

            $table->string('name',160)->nullable()->comment('Название документа');

            $table->date('docdate')->nullable()->comment('Дата документа');
            $table->string('docnum',36)->nullable()->comment('№ заявки');

            $table->string('notes',360)->nullable()->comment('Примечание');

            $table->bigInteger('statusid')->nullable()->unsigned()->comment('0-черновик, 1-открыт для изменения, 2-закрыт');

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
        Schema::dropIfExists('contract_workplans');
    }
}
