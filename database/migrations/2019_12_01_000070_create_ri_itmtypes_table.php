<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiItmtypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ri_itmtypes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('refitmid')->unsigned()->index('refitmid');
            $table->bigInteger('itmtypeid')->unsigned()->index('itmtypeid');

            $table->boolean('active')->default(1)
		->comment('1-используется');

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');


            $table->foreign('itmtypeid')->references('id')->on('itmtypes');
            $table->foreign('refitmid')->references('id')->on('refitems')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ri_itmtypes');
    }
}
