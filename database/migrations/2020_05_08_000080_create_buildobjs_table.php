<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuildobjsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buildobjs', function (Blueprint $table) {
            $table->id();
            $table->string('name',120);
            $table->string('descript',400)->nullable();
            $table->string('address',60)->nullable();

            $table->biginteger('projectid')->unsigned()->nullable()->index()->comment('Проект');

            $table->biginteger('orgid')->unsigned()->nullable()->index()->comment('Заказчик');
            $table->biginteger('ownorgid')->unsigned()->nullable()->index()->comment('Генподрядчик?');
            $table->biginteger('contractid')->unsigned()->nullable()->index();

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
        Schema::dropIfExists('buildobjs');
    }
}
