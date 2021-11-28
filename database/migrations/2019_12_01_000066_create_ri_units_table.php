<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ri_units', function (Blueprint $table) {

            $table->id();
        
	    $table->bigInteger('refitmid')->unsigned()->index('refitmid');
	            $table->foreign('refitmid')->references('id')->on('refitems')->onDelete('cascade');

	    $table->bigInteger('unittypeid')->unsigned()->index('unittypeid');
	            $table->foreign('unittypeid')->references('id')->on('unittypes')->onDelete('cascade');

            $table->decimal('k2ref_unit',16,8)->nullable()->comment('Коэффициент перевода в ЕИ референсной записи');

            $table->boolean('active')->default(1)
		->comment('1-используется');

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
        Schema::dropIfExists('ri_units');
    }
}
