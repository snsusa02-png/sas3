<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskRelationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_relations', function (Blueprint $table) {
            $table->id();
            $table->biginteger('masterid')->unsigned()->index();
            $table->biginteger('slaveid')->unsigned()->index();

            $table->foreign('masterid')
                ->references('id')
                ->on('tasks')
                ->onDelete('cascade');

            $table->foreign('slaveid')
                ->references('id')
                ->on('tasks')
                ->onDelete('cascade');

            //$table->primary(['masterid','slaveid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_relations');
    }
}
