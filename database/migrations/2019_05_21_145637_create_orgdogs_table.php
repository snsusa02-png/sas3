<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrgdogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orgdogs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('ownorgid')->unsigned()->index('ownorgid')
                ->comment('ID организации-владельца (по orgs.ID)');

            $table->bigInteger('orgid')->unsigned()->index('orgid')
                ->comment('ID организации-контрагента (по orgs.ID)');

            $table->string('dognum', 16)->nullable()->comment('Номер договора');
            $table->date('dogdate')->nullable()->comment('Дата договора');

            $table->string('descript', 60)->nullable()->comment('Описание договора');

            $table->boolean('active')->default(1)
                ->comment('1-признак подписанности, 0 - черновик');

            $table->date('begdate')->default(date("Y-m-d"))->comment('Начало действия');
            $table->date('enddate')->nullable()->comment('Окончание действия');

            $table->timestamp('created_at')->nullable()->useCurrent = true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
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
        Schema::dropIfExists('orgdogs');
    }
}
