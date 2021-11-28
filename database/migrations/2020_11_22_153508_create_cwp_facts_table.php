<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCwpFactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cwp_facts', function (Blueprint $table) {
            $table->id();

            $table->biginteger('workid')->unsigned()->index();
		$table->foreign('workid')->references('id')->on('cwp_works')->onDelete('cascade');

            $table->decimal('qty',12,3)->default(0)->comment('объем выполненных работ за указанный рабочий период');

            $table->dateTime('begdt')->nullable()->comment('начало рабочего периода');
            $table->dateTime('enddt')->comment('окончание рабочего периода');

            $table->string('notes',120)->nullable();


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
        Schema::dropIfExists('cwp_facts');
    }
}
