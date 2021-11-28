<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuildobjWrhsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buildobj_wrhs', function (Blueprint $table) {
            $table->id();

            $table->biginteger('buildobjid')->unsigned()->index();
		$table->foreign('buildobjid')->references('id')->on('buildobjs');

            $table->biginteger('wrhid')->unsigned()->comment('Склад');
		$table->foreign('wrhid')->references('id')->on('wrhs');

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
        Schema::dropIfExists('buildobj_wrhs');
    }
}
