<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrgpostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	//Справочник "Должности предприятия"

        Schema::create('orgposts', function (Blueprint $table) {

            $table->id();

            $table->biginteger('orgid')->unsigned()->comment('Связь с orgs');
		$table->foreign('orgid')->references('id')->on('orgs');

            $table->biginteger('depid')->unsigned()->comment('Связь с orgdeps');
		$table->foreign('depid')->references('id')->on('orgdeps');

            $table->string('name',90)->comment('Наименование должности');

            $table->integer('ordr')->unsigned()->nullable()->default(999);

            $table->decimal('stdlimunits',6,2)->nullable()->comment('Кол-во штатных единиц');
            $table->decimal('stdusedunits',6,2)->nullable()->default(0)->comment('Кол-во занятых штатных единиц');

            $table->decimal('tmplimunits',6,2)->nullable()->default(0)->comment('Кол-во временных штатных единиц');
            $table->decimal('tmpusedunits',6,2)->nullable()->default(0)->comment(', принятых на работу ВРЕМЕННО');

            $table->boolean('locked')->nullable()->default(0)->comment('0-можно редактировать/удалять, 1-нельзя');

            $table->string('grpcode',3)->nullable()->comment('Шифр строки формы 2-К (ГА)');

            $table->string('wrkduties',360)->nullable()->comment('Рабочие обязанности');
            $table->string('prsndmnds',360)->nullable()->comment('Требования к кандидату/работнику');
            $table->string('wrkconds',360)->nullable()->comment('Условия работы');

            $table->decimal('salary',12,2)->nullable()->comment('должностной оклад');
            $table->decimal('bns1pcnt',5,1)->nullable()->comment('надбавка 1 - процент от Salary');
            $table->decimal('bns1sum',12,2)->nullable()->comment('надбавка 1 - сумма = Bns1Pcnt  *  Salary / 100');
            $table->decimal('bns2pcnt',5,1)->nullable()->comment('надбавка 2 - процент от Salary');
            $table->decimal('bns2sum',12,2)->nullable()->comment('надбавка 2 - сумма = Bns2Pcnt  *  Salary / 100');
            $table->decimal('bns3sum',12,2)->nullable()->comment('надбавка 3');

            $table->tinyinteger('privacdays')->unsigned()->nullable()->comment('продолжительность основного отпуска');
            $table->tinyinteger('secvacdays')->unsigned()->nullable()->comment('продолжительность доп. отпуска');

            $table->biginteger('dutyprfid')->unsigned()->nullable()->comment('')->comment('ID базового профиля обязанностей, соответствующего данной должности (exam.SklProfiles)');
            $table->biginteger('schdltypeid')->unsigned()->nullable()->comment('Рабочий график - по-умолчанию. Может отличаться для конкретного сотрудника');

            $table->integer('prioritystep')->unsigned()->nullable()->comment('Шаг приоритета, который может установить сотрудник в данной должности (для системы "Задачи")');

            $table->boolean('active')->nullable()->default(1)->comment('');

            $table->date('begdate')->nullable()->comment('дата введения должности в штатное расписание')->useCurrent=true;
            $table->date('enddate')->nullable()->comment('дата вывода должности из штатного расписания');

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
        Schema::dropIfExists('orgposts');
    }
}
