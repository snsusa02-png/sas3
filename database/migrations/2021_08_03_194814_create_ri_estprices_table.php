<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiEstpricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ri_estprices', function (Blueprint $table) {
            $table->id();

	    $table->bigInteger('refitmid')->unsigned()->index('refitmid');
	            $table->foreign('refitmid')->references('id')->on('refitems')->onDelete('cascade');

            $table->date('begdate')->comment('Начало действия цены')->useCurrent=true;
            $table->date('enddate')->comment('Окончание действия цены')->nullable();

            $table->decimal('price', 12, 2)->nullable()->comment('Текущая отпускная цена');

	    $table->bigInteger('suporgid')->unsigned()->index('suporgid');
	            $table->foreign('suporgid')->references('id')->on('orgs')->onDelete('cascade');

            $table->Integer('supwrkdays')->nullable()->unsigned()->comment('срок поставки в рабочих днях от даты оплаты');
            $table->string('sup_notes',160)->nullable()->comment('Условия поставки');

            $table->boolean('active')->default(1)->comment('1-используется');

	    $table->bigInteger('src_sysobjid')->unsigned();
	    $table->bigInteger('src_objid')->unsigned();

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');

            $table->index(['src_sysobjid','src_objid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ri_estprices');
    }
}
