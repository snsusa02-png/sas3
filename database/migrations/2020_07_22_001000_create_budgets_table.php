<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBudgetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->biginteger('parid')->unsigned()->nullable()->index()->comment('Родительская запись');
//		$table->foreign('parid')->references('id')->on('budgets');

            $table->string('name',160)->comment('Наименование бюджета');

            $table->bigInteger('orgid')->nullable()->unsigned();
		$table->foreign('orgid')->references('id')->on('orgs')->comment('ЦФО (владелец бюджета)');

            $table->biginteger('projid')->unsigned()->nullable()->index()->comment('Проект');
		$table->foreign('projid')->references('id')->on('projects');

            $table->biginteger('buildobjid')->unsigned()->nullable()->index();
		$table->foreign('buildobjid')->references('id')->on('buildobjs');

            $table->bigInteger('par_contractid')->nullable()->unsigned();
		$table->foreign('par_contractid')->references('id')->on('contracts')->comment('Договор с родительским ЦФО');

            $table->bigInteger('par_orgid')->nullable()->unsigned()->comment('может быть заполнено для корневых бюджетов');

 	   $table->decimal('opersum',12,2)->nullable()->comment('текущая сумма (остаток) бюджета');

            $table->boolean('gen_exe_from_upd')->default(false);

            $table->boolean('active')->default(true);
            $table->boolean('closed')->default(false);

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
        Schema::dropIfExists('budgets');
    }
}
