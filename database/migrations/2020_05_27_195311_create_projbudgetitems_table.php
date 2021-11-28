<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjbudgetitemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	//Статьи бюджета проекта 

        Schema::create('projbudgetitems', function (Blueprint $table) {
            $table->id();
            $table->biginteger('projectid')->unsigned()->index('projid')
		->comment('Связан с проектом');

            $table->string('name',160)->comment('Название статьи бюджета');
            $table->string('descript',300)->nullable();
            $table->decimal('limsum',12,2)->nullable()->comment('Лимит бюджета, руб');
            $table->decimal('cursum',12,2)->nullable()->comment('Текущий остаток средств, руб');

            $table->boolean('active')->nullable()->default(0);
            $table->integer('ordr')->unsigned()->nullable();

	
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
        Schema::dropIfExists('projbudgetitems');
    }
}
