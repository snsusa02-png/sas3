<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractOrgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	//Контрагенты(стороны) по договору
        Schema::create('contract_orgs', function (Blueprint $table) {

            $table->id();
            $table->biginteger('contractid')->unsigned()->index();
            $table->biginteger('orgid')->unsigned()->nullable()->index()->comment('если null - подходит для любой организации');

            $table->biginteger('roleid')->unsigned()->nullable()->index()->comment('1-ownorg, 2-org');
            $table->string('rolename',120)->nullable()->comment('роль участника (Заказчик/Исполнитель)');

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
        Schema::dropIfExists('contract_orgs');
    }
}
