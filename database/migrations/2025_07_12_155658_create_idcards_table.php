<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIdcardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('idcards', function (Blueprint $table) {
            $table->id();
            $table->string('num',16)->comment('Номер карты');
            $table->string('name',60)->nullable()->comment('Название/тип карты');

            $table->bigInteger('orgid')->unsigned()->nullable()->comment('Компания-владелец карты')->index('orgid');
		$table->foreign('orgid')->references('id')->on('orgs');

//            $table->bigInteger('curstaffid')->unsigned()->nullable()->comment('сотрудник, текущий держатель карты')->index('curstaffid');
//		$table->foreign('curstaffid')->references('id')->on('orgstaff');

            $table->string('notes',300)->nullable()->comment('Примечание');

            $table->boolean('active')->default(1);

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
        Schema::dropIfExists('idcards');
    }
}
