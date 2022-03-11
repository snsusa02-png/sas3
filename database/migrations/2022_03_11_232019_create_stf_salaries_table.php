<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStfSalariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stf_salaries', function (Blueprint $table) {
            $table->id();

            $table->biginteger('staffid')->unsigned()->nullable()->comment('Сотрудник');
		$table->foreign('staffid')->references('id')->on('orgstaff');

            $table->date('wrkbegdate')->comment('начало рабочего периода (1-й рабочий день месяца)');
            $table->date('wrkenddate')->nullable()->comment('конец рабочего периода (последний рабочий день месяца)');
            $table->decimal('wrkdays',2,0)->nullable()->comment('кол-во рабочих дней в месяце');
            $table->decimal('wrkhrs',4,1)->nullable()->comment('кол-во рабочих часов в месяце');

            $table->decimal('salary_sum',12,2)->default(0)->comment('Общая сумма ЗП');

            $table->string('notes',300)->nullable()->comment('Примечание');

            $table->boolean('active')->default(true);

            $table->timestamp('approved_at')->nullable();
            $table->bigInteger('approved_by')->nullable()->unsigned()
		->Comment('Кто согласовал ЗП');

            $table->boolean('locked')->default(false);

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
        Schema::dropIfExists('stf_salaries');
    }
}
