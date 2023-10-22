<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStfWrkhrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stf_wrkhrs', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('staffid')->unsigned()->comment('id сотрудника')->index('staffid');
		$table->foreign('staffid')->references('id')->on('orgstaff');

            $table->int('yr');
            $table->tinyint('mn');
            $table->date('forbegdate')->comment('начислено за период, начало');
            $table->date('forenddate')->comment('начислено за период, конец');

            $table->string('day_hrs',100)->nullable()->comment('Список рабочих часов в дневное время');
            $table->string('night_hrs',100)->nullable()->comment('Список рабочих часов в ночное время');

	    $table->decimal('day_tot_hrs', 5,1)->nullable()->comment('Общее кол-во часов работы днем');
	    $table->decimal('night_tot_hrs', 5,1)->nullable()->comment('Общее кол-во часов работы ночью');

	    $table->decimal('day_hr_cost', 8,2)->nullable()->comment('Ставка за час работы днем');
	    $table->decimal('night_hr_cost', 8,2)->nullable()->comment('Ставка за час работы ночью');

	    $table->decimal('day_tot_sum', 12,2)->nullable()->comment('Общая сумма за работу днем');
	    $table->decimal('night_tot_sum', 12,2)->nullable()->comment('Общая сумма за работу ночью');
	    $table->decimal('tot_sum', 12,2)->nullable()->comment('Общая сумма за работу');

            $table->string('notes',160)->nullable();

            $table->boolean('active')->default(1)
                ->comment('1-признак активности, 0 - черновик');

            $table->timestamp('created_at')->nullable()->useCurrent = true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');

            $table->index(['yr','mn']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stf_wrkhrs');
    }
}
