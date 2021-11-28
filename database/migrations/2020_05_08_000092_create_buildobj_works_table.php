<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuildobjWorksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buildobj_works', function (Blueprint $table) {
            $table->id();

            $table->date('workdate')->comment('Дата работ')->useCurrent=true;

            $table->biginteger('buildobjid')->unsigned()->nullable()->index();
		$table->foreign('buildobjid')->references('id')->on('buildobjs');

            $table->biginteger('buildopertypeid')->unsigned()->nullable()->comment('Вид Работ');
		$table->foreign('buildopertypeid')->references('id')->on('buildopertypes');

            $table->biginteger('orgid')->unsigned()->nullable()->index()->comment('Id организации (orgs)');
		$table->foreign('orgid')->references('id')->on('orgs');

            $table->string('workname',160)->nullable()->comment('Описание работ');

            $table->integer('wrkr_qty')->unsigned()->nullable()->comment('кол-во рабочих');
            $table->integer('mech_qty')->unsigned()->nullable()->comment('кол-во техники');

            $table->string('notes',300)->nullable();

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
        Schema::dropIfExists('buildobj_works');
    }
}
