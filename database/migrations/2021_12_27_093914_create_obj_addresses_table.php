<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obj_addresses', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('sysobjid')->unsigned();
		$table->foreign('sysobjid')->references('id')->on('sysobjs');

            $table->bigInteger('objid')->unsigned();

            $table->bigInteger('addresstypeid')->unsigned()->nullable()->default(2)->comment('ID типа адреса: 1-юрид, 2-фактический, ...');
            $table->string('zip', 6)->nullable()->comment('Индекс');
            $table->string('country', 30)->nullable()->comment('Страна');
            $table->string('region', 60)->nullable()->comment('Край/Область');
            $table->string('district', 60)->nullable()->comment('Район');
            $table->string('city', 30)->nullable()->comment('Город');
            $table->string('street_adr', 60)->nullable()->comment('улица, копус/дом, офис/квартира');

            $table->string('streettype', 16)->nullable()->comment('тип улицы: ул, проспект, переулок, ...');
            $table->string('street', 60)->nullable()->comment('название улицы');
            $table->string('corpus', 16)->nullable()->comment('№ корпуса');
            $table->string('building', 16)->nullable()->comment('№ дома');
            $table->string('appartment', 16)->nullable()->comment('№ офиса/квартиры');
            $table->string('address', 160)->nullable()->comment('Сводный адрес');

            $table->string('c_lat',24)->nullable()->comment('Широта (гео) 0..90, например 66° 34′ (66.57°) N');
            $table->string('c_lon',24)->nullable()->comment('Долгота (гео) 0..180. 166° 34′ (166.57°) N');

            $table->string('notes', 120)->nullable()->comment('Примечание');

            //$table->boolean('private')->default(0)->comment('0-общедоступно; 1-приватное, только для автора и получателей');

            $table->boolean('active')->default(1)->comment('0-черновик; 1-отображать');


            $table->timestamp('created_at')->nullable()->useCurrent = true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');

            $table->index(['sysobjid','objid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('obj_addresses');
    }
}
