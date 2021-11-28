<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrgdepsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orgdeps', function (Blueprint $table) {
            $table->id();
            $table->biginteger('parid')->unsigned()->nullable()->index('parid');
            $table->biginteger('orgid')->unsigned()->index('orgid');
		$table->foreign('orgid')->references('id')->on('orgs');

            $table->string('name',160);
            $table->string('code',10)->nullable();

            $table->biginteger('mngr_staffid')->unsigned()->nullable();
            $table->string('mngr_name',60)->nullable();

            $table->integer('ordr')->unsigned()->nullable()->default(999);

            $table->boolean('active')->nullable()->default(1);
            $table->dateTime('begdt')->nullable();
            $table->dateTime('enddt')->nullable();

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
        Schema::dropIfExists('orgdeps');
    }
}
