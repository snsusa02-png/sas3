<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orgs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->tinyinteger('kindid')->default(1)->comment('1-Ю/Л, 2-Ф/Л');

            $table->string('name',120);
            $table->string('fullname',300)->nullable()->comment('Официальное название - для документов');
            $table->string('inn',12)->nullable();
            $table->string('kpp',9)->nullable();
            $table->string('okpo',8)->nullable();
            $table->string('ogrn',13)->nullable()->comment('ОГРН - Основной государственный регистрационный номер');
            $table->string('ogrnip',15)->nullable()->comment('ОГРН - Основной государственный регистрационный номер индивидуального предпринимателя');;
            $table->tinyInteger('taxsysid')
                    ->nullable()
                    ->unsigned()
                    ->default(1)
                    ->comment('тип системы налогового учета для организации');

            $table->string('address',160)->nullable()->comment('сводный адрес: индекс, город, улица, дом, корпус, офис');
            $table->string('phone',20)->nullable()->comment('Общий телефон/приемная');
            $table->string('email',36)->nullable()->comment('Общий email');

            $table->string('boss_postname',120)->nullable()->comment('Генеральный директор');
            $table->bigInteger('boss_staffid')->unsigned()->nullable()->comment('id сотрудника - руководителя предприятия');
//		$table->foreign('boss_staffid')->references('id')->on('orgstaff')->onDelete('set null');
            $table->string('boss_name',60)->nullable()->comment('Иванов И.С.');
            $table->string('boss_fullname',90)->nullable()->comment('Иванов Иван Сергеевич');

            $table->string('ca_postname',120)->nullable()->comment('Главный бухгалтер');
            $table->bigInteger('ca_staffid')->unsigned()->nullable()->comment('id сотрудника - руководителя предприятия');
//		$table->foreign('ca_staffid')->references('id')->on('orgstaff')->onDelete('set null');;
            $table->string('ca_name',60)->nullable()->comment('Иванов И.С.');
            $table->string('ca_fullname',90)->nullable()->comment('Иванов Иван Сергеевич');

            $table->string('bank_account_info',300)->nullable()->comment('сводная информация о банковских реквизитах организации');

            $table->string('main_activity',300)->nullable()->comment('Основной вид деятельности');

            $table->string('notes',300)->nullable()->comment('Примечание');
            $table->string('iddoc_info',160)->nullable()->comment('Паспортные данные');

            $table->boolean('active')->default(true);

            $table->string('orgtype',10)->nullable();
            $table->string('contact_phone',20)->nullable();
            $table->string('contact_name',550)->nullable();

            $table->date('begdate')->nullable();
            $table->date('enddate')->nullable();

            $table->bigInteger('discounttypeid')->unsigned()->nullable()
		->comment('id типа применяемой скидки');

            $table->tinyInteger('rating')->nullable()->comment('-1; 0; +1 рейтинг');
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
        Schema::dropIfExists('paydocs');
        Schema::dropIfExists('bankpays');
        Schema::dropIfExists('org_curators');
        Schema::dropIfExists('org_saldos');
        Schema::dropIfExists('orgstaff');
        Schema::dropIfExists('orgdeps');

        Schema::dropIfExists('orgs');
    }
}
