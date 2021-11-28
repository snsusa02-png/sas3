<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrgExtservicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('org_extservices', function (Blueprint $table) {
            $table->id();

            $table->string('name',60)->comment('Название услуги');
            $table->string('descript',360)->nullable()->comment('Описание услуги');

            $table->bigInteger('orgid')->unsigned();
		$table->foreign('orgid')->references('id')->on('orgs')->comment('Потребитель услуги');

            $table->biginteger('srvcorgid')->unsigned()->nullable()->index()->comment('Провайдер услуги');
		$table->foreign('srvcorgid')->references('id')->on('orgs');

	    $table->biginteger('contractid')->unsigned()->nullable()->index();
		$table->foreign('contractid')->references('id')->on('contracts');

            $table->string('lk_url',60)->comment('Ссылка на ЛК провайдера услуги');

            $table->bigInteger('extsysid')->unsigned()->nullable();
		$table->foreign('extsysid')->references('id')->on('extsystems')->comment('Внешняя ИС');

            $table->decimal('notify_limsum',12,2)->nullable()->comment('лимит на уведомление об остатке, руб');
            $table->decimal('lock_limsum',12,2)->nullable()->comment('лимит на блокировку (min), руб');

            $table->tinyinteger('inform_limdays')->unsigned()->nullable()->comment('включить в информер, если прогноз кол-ва дней до блокировки <= этому значению');

            $table->decimal('rest_sum',12,2)->nullable()->comment('Последний известный остаток, руб');
            $table->datetime('rest_dt')->nullable()->comment('Дата/время данных')->useCurrent=true;

            $table->decimal('pre_rest_sum',12,2)->nullable()->comment('Предыдущий остаток, руб');
            $table->decimal('day_sum_diff',12,2)->nullable()->comment('расход в день от предыдущего значения, руб');
            $table->decimal('avg_daysum',12,2)->nullable()->comment('Средний расход в рабочий день, руб');

            $table->string('username',32)->nullable()->comment('Пользователь ЛК');
            $table->string('password',16)->nullable()->comment('Пароль пользователя ЛК');

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
        Schema::dropIfExists('org_extservices');
    }
}
