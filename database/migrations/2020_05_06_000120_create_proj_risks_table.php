<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjRisksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proj_risks', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('projid')->unsigned()->comment('id проекта');
		$table->foreign('projid')->references('id')->on('projects')->onDelete('cascade');

            $table->string('name',120)->nullable();
            $table->string('descript',300)->nullable();
            $table->string('category',90)->nullable()->comment('род/категория риска');

            $table->tinyinteger('severity')->unsigned()->default(0)->comment('важность последствий');
            $table->tinyinteger('chance')->unsigned()->default(0)->comment('вероятность');
            $table->tinyinteger('weight')->unsigned()->default(0)->comment('weight=severity*chance');

            $table->string('decision',300)->nullable()->comment('возможное решение');

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
        Schema::dropIfExists('proj_risks');
    }
}
