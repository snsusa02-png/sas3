<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWrhsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Товарные склады компании
        //20190405 SNS
        Schema::create('wrhs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name',60)->comment('Название склада');
            $table->string('descript',120)->nullable()->comment('Описание склада');
            $table->string('address',120)->nullable()->comment('Адрес склада');

            $table->bigInteger('orgid')->nullable()->unsigned()->nullable()
                ->comment('организация-владелец склада');

            $table->boolean('forsale')->default(1);
            $table->boolean('forlost')->default(0);
            $table->boolean('active')->default(1);

            $table->bigInteger('locked_by')->nullable()->unsigned()
                ->comment('UserID, захватившего склад для проведения транзакции');

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
        Schema::dropIfExists('ri_wrhboxes');

        Schema::dropIfExists('wrhs');
    }
}
