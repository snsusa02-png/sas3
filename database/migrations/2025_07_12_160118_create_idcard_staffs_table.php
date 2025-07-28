<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIdcardStaffsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('idcard_staffs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('cardid')->unsigned()->comment('id карты')->index('cardid');
	    $table->foreign('cardid')->references('id')->on('idcards');

            $table->bigInteger('staffid')->unsigned()->nullable()->comment('сотрудник, держатель карты')->index('staffid');
	    $table->foreign('staffid')->references('id')->on('orgstaff');

            $table->date('begdate')->default(date("Y-m-d"))->comment('Начало владения');
            $table->date('enddate')->nullable()->comment('Окончание владения');

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
        Schema::dropIfExists('idcard_staffs');
    }
}
