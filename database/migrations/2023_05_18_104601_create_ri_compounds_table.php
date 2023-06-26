<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiCompoundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ri_compounds', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('ownorgid')->unsigned()->nullable()->index('ownorgid')
		->comment('Производитель');

	    $table->bigInteger('refitmid')->unsigned()->index('refitmid');
	            $table->foreign('refitmid')->references('id')->on('refitems');

            $table->date('begdate')->useCurrent=true;
            $table->date('enddate')->nullable();

            $table->string('notes',90)->nullable();

            $table->tinyinteger('active')->default(1)->comment('0-черновик,1-активно');

            $table->boolean('docsigned')->default(0)->comment('1-признак подписанности, 0 - черновик');

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');

            $table->timestamp('signed_at')->nullable();
            $table->bigInteger('signed_by')->nullable()->unsigned()
                ->comment('UserID, утвердившего рецептуру');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ri_compounds');
    }
}
