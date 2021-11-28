<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnittypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	//Справочник единиц измерения
        Schema::create('unittypes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_by')->unsigned()->nullable();
		$table->foreign('parent_by')->references('id')->on('unittypes');

            $table->string('name',16);
            $table->string('descript',60)->nullable();
            $table->tinyInteger('decimal_dgts')->unsigned()->default(3)->comment('Кол-во знаков после запятой для количества');

            $table->decimal('k2prnt_unit',10,4)->nullable()->comment('Коэффициент перевода в базовую ЕИ');
            $table->boolean('active')->default(1);

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('StaffID, изменившего запись');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unittypes');
    }
}
