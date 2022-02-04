<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Helpers\DBTools;

class CreateOrgCuratorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('org_curators', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('orgid')->unsigned()->index('orgid')
                    ->comment('ID курируемой организации');

            $table->biginteger('staffid')->nullable()->unsigned()->index('staffid')
                    ->comment('ID сотрудника-куратора (orgstaff.id)');
            $table->biginteger('userid')->nullable()->unsigned()->index('userid')
                    ->comment('ID пользователя-куратора');

            $table->bigInteger('roleid')->unsigned()->index('roleid')->default(1);

            $table->biginteger('opertypeid')->unsigned()->nullable()->comment('Курируемый вид работ(деятельности). Null - все');
//		$table->foreign('opertypeid')->references('id')->on('opertypes');

            $table->timestamp('begdt')->nullable()->useCurrent=true;
            $table->timestamp('enddt')->nullable();

            $table->boolean('active')->default(1);

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
        Schema::dropIfExists('org_curators');
    }
}
