<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMchncontrorgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mchncontrorgs', function (Blueprint $table) {
            $table->id();

            $table->biginteger('machineid')->unsigned()->index()->comment('ID техники, по Machines.id');
            $table->biginteger('orgid')->unsigned()->index()->comment('Организация, управляющая этой техникой');
            $table->dateTime('begdate')->nullable();
            $table->dateTime('enddate')->nullable();

            $table->biginteger('contractid')->unsigned()->nullable()->comment('id договора');
            $table->string('contractnum',36)->nullable();
            $table->date('contractdate')->nullable();

            $table->string('descript',300)->nullable()->comment('');

            $table->boolean('active')->default(true);
            //$table->boolean('signed')->default(true)->comment('1-Подписан (действующий)');


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
        Schema::dropIfExists('mchncontrorgs');
    }
}
