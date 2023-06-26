<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiCmpndItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ri_cmpnd_items', function (Blueprint $table) {
            $table->id();
	    $table->bigInteger('cmpndid')->unsigned()->index('cmpndid');
	            $table->foreign('cmpndid')->references('id')->on('ri_compounds');

	    $table->bigInteger('refitmid')->unsigned()->index('refitmid');
	            $table->foreign('refitmid')->references('id')->on('refitems');

            $table->decimal('min_qty',12,3)->comment('Минимально-допустимое кол-во ЕИ материала (refitmId)');
            $table->decimal('max_qty',12,3)->comment('Максимально-допустимое кол-во ЕИ материала (refitmId)');

            $table->string('notes',90)->nullable();


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
        Schema::dropIfExists('ri_cmpnd_items');
    }
}
