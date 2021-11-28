<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserorgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	//Спр-к - Какие организации представляет пользователь

        Schema::create('userorgs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('userid')->unsigned()->index('userid')
                ->comment('ID пользователя (по Users.ID)');
            $table->bigInteger('orgid')->unsigned()->index('orgid')
                ->comment('ID представляемой организации (по orgs.ID)');
//            $table->bigInteger('staffid')->unsigned()->nullable()
//                ->index('staffid')
//                ->comment('ID сотрудника в организации (по orgstaff.ID)');
            $table->string('postname',60)->nullable()->comment('Название должности');

//            $table->boolean('curator')->default(0)->comment('контролирует все заказы этого клиента');
// перенесено в отдельный справочник org_curators

            $table->dateTime('begdt')
                ->comment('Начало периода представительства интересов организации')
                ->useCurrent = true;
            $table->dateTime('enddt')->nullable()
                ->comment('Окончание периода. Нужно заполнять автоматически');

            $table->boolean('acs_contracts')->nullable()->default(0)
                ->comment('Предоставлять доступ к контрактам представляемой организации');

//            $table->boolean('acs_orgplnpays')->nullable()->default(0)
//                ->comment('Предоставлять доступ к плану платежей представляемой организации');

            $table->boolean('active')->default(1);

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
        Schema::dropIfExists('userorgs');
    }
}
