<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrgPlacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('org_places', function (Blueprint $table) {
            $table->id();
            $table->biginteger('orgid')->unsigned()->index('orgid');
		$table->foreign('orgid')->references('id')->on('orgs');

            $table->biginteger('placeid')->unsigned()->index('placeid')->comment('places.id');
		$table->foreign('placeid')->references('id')->on('places');

            $table->string('name',160)->nullable()->comment('Название места');

            $table->biginteger('placetypeid')->unsigned()->comment('id типа места: 1-офис, 2-склад');

            $table->string('address',160)->nullable()->comment('сводный адрес: индекс, город, улица, дом, корпус, офис');

            $table->boolean('active')->nullable()->default(1);

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
        Schema::dropIfExists('org_places');
    }
}
