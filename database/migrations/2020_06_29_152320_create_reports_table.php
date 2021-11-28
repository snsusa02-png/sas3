<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->string('name',160)->nullable()->comment('Название отчета');
            $table->string('descript',360)->nullable()->comment('Описание отчета');

            $table->string('handler',300)->nullable()->comment('Обработчик');
            $table->string('route',120)->nullable()->comment('полный маршрут route(mchnrqsts.rep01,{id})');

            $table->biginteger('acsid')->unsigned()->default(1)->index()->comment('категория информации - для доступа');
		$table->foreign('acsid')->references('id')->on('acs');

            $table->biginteger('acs_rightid')->unsigned()->default(1)->index()->comment('право, необходимое для доступа пользователя');
		$table->foreign('acs_rightid')->references('id')->on('sysfuncs');

            $table->boolean('public')->default(true)->comment('0-приватный, 1-публичный. Если 0 - ориентируемся на вхождение пользователя в obj_readers');

            $table->boolean('active')->default(true);

            $table->biginteger('use_cnt')->unsigned()->default(0)->comment('кол-во просмотров/использования');
            $table->dateTime('lastuse_dt')->nullable()->comment('когда запрашивали в крайний раз');
            $table->biginteger('lastuse_userid')->unsigned()->nullable()->comment('Кто запрашивал в крайний раз');
            $table->string('lastuse_username',120)->nullable()->comment('Кто запрашивал в крайний раз');

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
        Schema::dropIfExists('reports');
    }
}
