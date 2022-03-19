<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskReptypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_reptypes', function (Blueprint $table) {
            $table->id();

            $table->string('name',90);
            $table->string('descript',300)->nullable();
            $table->tinyinteger('days_interval')->unsigned()->nullable()
		->default(250)
		->comment('периодичность отчет по работе');

            $table->tinyinteger('ordr')
		    ->unsigned()
                    ->nullable()
 		    ->default(250)
		    ->comment('порядок вывода в списках');

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
        Schema::dropIfExists('task_reptypes');
    }
}
