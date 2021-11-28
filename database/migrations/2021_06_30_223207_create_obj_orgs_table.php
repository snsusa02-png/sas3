<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjOrgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obj_orgs', function (Blueprint $table) {
            $table->id();

            $table->biginteger('sysobjid')->unsigned();
                $table->foreign('sysobjid')->references('id')->on('sysobjs');
            $table->biginteger('objid')->unsigned();


            $table->biginteger('orgid')->unsigned()->index()->comment('');
            $table->string('orgdepname',60)->nullable()->comment('Название подразделения организации');

            $table->unsignedBigInteger('roletypeid')->comment('тип роли пользователя');
            $table->foreign('roletypeid')->references('id')->on('roletypes');

            $table->string('rolename', 60)->nullable()->comment('Название роли, если не из справочника');

            $table->integer('ordr')->unsigned()->nullable()->default(999)->comment('примерный порядок');
            $table->boolean('active')->default(true);

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
        Schema::dropIfExists('obj_orgs');
    }
}
