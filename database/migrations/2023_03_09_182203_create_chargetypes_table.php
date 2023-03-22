<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargetypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chargetypes', function (Blueprint $table) {
            $table->id();
	    
            $table->Integer('dir')->comment('-1 - Удержание, +1 - Начисление');
            $table->string('name',160)->comment('Наименование удержания/начисления');
            $table->string('descript',360)->nullable()->comment('Описание');

            $table->boolean('active')->default(true);
            $table->integer('ordr')->unsigned()->default(999999);

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
        Schema::dropIfExists('chargetypes');
    }
}
