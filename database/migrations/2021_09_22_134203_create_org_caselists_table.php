<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrgCaselistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('org_caselists', function (Blueprint $table) {
            $table->id();

            $table->biginteger('orgid')->unsigned()->index()->comment('Владелец Номенклатуры дел');
	            $table->foreign('orgid')->references('id')->on('orgs');

            $table->string('docnum',16)->nullable();
            $table->date('docdate')->nullable();

            $table->date('begdate');
            $table->date('enddate');

            $table->tinyinteger('statusid')->default(1)->comment('0-черновик,1-на согласовании,2-согласован');

            $table->bigInteger('stfsign1_by')->nullable()->unsigned()->comment('staffID, утвердившего Номенклатуру дел от руководителя организации');
            $table->timestamp('stfsign1_at')->nullable();

            $table->bigInteger('stfsign2_by')->nullable()->unsigned()->comment('staffID, утвердившего Номенклатуру дел начальника отдела делопроизводства');
            $table->timestamp('stfsign2_at')->nullable();

            $table->bigInteger('stfsign3_by')->nullable()->unsigned()->comment('staffID, утвердившего Номенклатуру дел от ответственного за архив');
            $table->timestamp('stfsign3_at')->nullable();


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
        Schema::dropIfExists('org_caselists');
    }
}
