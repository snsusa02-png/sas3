<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrgNamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	// 'Альтернативные названия кнтрагентов'

        Schema::create('org_names', function (Blueprint $table) {
            $table->id();

            $table->biginteger('orgid')->unsigned()->comment('id организации')->index();
	            $table->foreign('orgid')->references('id')->on('orgs');

            $table->biginteger('nametypeid')->unsigned()->nullable()->comment('id типа имени (по orgnametypes)');
	            $table->foreign('nametypeid')->references('id')->on('orgnametypes');
            
		$table->string('lang',2)->default('ru')->comment('Язык');

            $table->string('name',300)->comment('Название');

            $table->date('begdate')->comment('Начало действия имени');
            $table->date('enddate')->nullable()->comment('Окончание действия имени');

            $table->boolean('dfltforfindoc')->default(false)->comment('Использовать для фин документов');

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
        Schema::dropIfExists('org_names');
    }
}
