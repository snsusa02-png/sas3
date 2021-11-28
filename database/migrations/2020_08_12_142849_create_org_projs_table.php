<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrgProjsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('org_projs', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('orgid')->nullable()->unsigned();
		$table->foreign('orgid')->references('id')->on('orgs')->comment('ЦФО');

            $table->biginteger('projid')->unsigned()->nullable()->index()->comment('Проект');
		$table->foreign('projid')->references('id')->on('projects');

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
        Schema::dropIfExists('org_projs');
    }
}
