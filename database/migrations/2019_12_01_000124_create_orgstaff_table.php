<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrgstaffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orgstaff', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->biginteger('orgid')->unsigned()->index('orgid');
		$table->foreign('orgid')->references('id')->on('orgs');

            $table->biginteger('userid')->unsigned()->nullable()->index('userid');
		$table->foreign('userid')->references('id')->on('users');

            $table->string('lname',30);
            $table->string('fname',30)->nullable();
            $table->string('mname',30)->nullable();
            $table->string('name',90)->nullable();

            $table->biginteger('depid')->unsigned()->nullable();
		$table->foreign('depid')->references('id')->on('orgdeps');

            $table->string('depname',60)->nullable();

            $table->biginteger('postid')->unsigned()->nullable();
		$table->foreign('postid')->references('id')->on('orgposts');

            $table->string('postname',160)->nullable();

            $table->decimal('stdpostunit',4,2)->nullable()->default(0)->comment('Занимаемое кол-во постоянных ставок');

            $table->decimal('tmppostunit',4,2)->nullable()->default(0)->comment('Занимаемое кол-во временных ставок');
            $table->date('tmpenddate')->nullable()->comment('Дата окончания временной работы');

            $table->string('bossname',36)->nullable()->comment('ФИО руководителя');

            $table->string('reg_address',160)->nullable()->comment('адрес регистрации: индекс, город, улица, дом, корпус, офис');
            $table->string('phone',36)->nullable()->comment('телефон сотрудника');
            $table->string('email',36)->nullable()->comment('email сотрудника');

            $table->date("birthdate")->nullable()->comment('Дата рождения');
            $table->boolean('bd_private')->nullable()->default(0)->comment('1-Не отображать на информерах');
            $table->string("sex",1)->nullable()->comment('Пол: M-муж, F-жен');

            $table->string("birthplace",50)->nullable()->comment('Место рождения');

            $table->string("inn",12)->nullable()->comment('ИНН');
            $table->string("snils",14)->nullable()->comment('СНИЛС');

            $table->tinyInteger("education_lvl")->nullable()->comment('Уровень образования');
            $table->tinyInteger("marriage")->nullable()->comment('Состояние в браке: 0-не женат, 1-женат');

            $table->string("citizenship",30)->nullable()->comment('Гражданство');

            $table->boolean('active')->default(1);
            $table->date('begdate')->nullable()->comment('Дата приема на работу');
            $table->date('enddate')->nullable()->comment('Дата увольнения с работы');

            $table->boolean('outofoffice')->nullable()->default(0)->comment('1-признак отсутствия на р/месте - отпуск/командировка/больничный');
            $table->string("ooo_reason",30)->nullable()->comment('Причина отсутствия: отпуск/командировка/больничный');
            $table->date('ooo_tilldate')->nullable()->comment('Отсутствует до даты');

            $table->string('jobduties',500)->nullable()->comment('Рабочие обязанности');

            $table->string('gendoctypes',300)->nullable()->comment('Создаваемые документы');
            $table->string('cnfrmdoctypes',300)->nullable()->comment('Согласуемые документы');
            $table->string('aprvdoctypes',300)->nullable()->comment('Утверждаемые документы');

            $table->string('rqrd_software',300)->nullable()->comment('Требуемое ПО');
            $table->string('have_software',300)->nullable()->comment('Установленное ПО');

 	   $table->biginteger('fot_acnttypeid')->unsigned()->nullable()->index()->comment('тип статьи расхода - источника ФОТ');
		$table->foreign('fot_acnttypeid')->references('id')->on('bdgtacnttypes');

 	   $table->decimal('hour_salary',7,2)->nullable()->comment('ставка ЗП в час');
 	   $table->decimal('day_salary',7,2)->nullable()->comment('ставка ЗП в день');

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
        Schema::dropIfExists('orgstaff');
    }
}
