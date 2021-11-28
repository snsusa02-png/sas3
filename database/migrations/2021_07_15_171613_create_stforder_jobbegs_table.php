<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStforderJobbegsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

	/*
	Специфическая часть приказа о приеме/переводе на другую работу
	*/
        Schema::create('stforder_jobbegs', function (Blueprint $table) {
            
	    $table->id();

            $table->string('jobtype',20)->nullable()->comment('основная/временнная/по совместительству');
            $table->decimal('jobfraction',4,2)->unsigned()->default(1)->comment('Часть ставки. 1- полная ставка; 0.5 - полставки и т.д.');

            $table->biginteger('schdltypeid')->unsigned()->nullable()->comment('Тип назначенного рабочего графика сотрудника (по WrkSchdlTypes)');
        
	    $table->string('movtype',9)->nullable()->comment('вид перевода (постоянно, временно)');
        
	    $table->decimal('salary',10,2)->unsigned()->nullable()->comment('Часть ставки. 1- полная ставка; 0.5 - полставки и т.д.');
            $table->string('bonus',40)->nullable()->comment('');
            
	    $table->boolean('testterm')->default(1)->comment('');
            $table->string('altdepname',80)->nullable()->comment('Альтернативное название подразделения');
            $table->biginteger('sbststaffid')->unsigned()->nullable()->comment('при временном приеме/переводе здесь может быть StaffID замещаемого сотрудника');


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
        Schema::dropIfExists('stforder_jobbegs');
    }
}
