<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuildopertypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buildopertypes', function (Blueprint $table) {
            $table->id();

//            $table->foreignId('buildobjid')->nullable()->constrained('buildobjs')->cascadeOnDelete();
            $table->biginteger('buildobjid')->unsigned()->nullable()->index();
            $table->foreignId('parid')->nullable()->constrained('buildopertypes')->cascadeOnDelete();

            $table->string('name',160);
            $table->biginteger('baseworktypeid')->unsigned()->nullable()->index()->comment('Базовый тип работ');

            $table->string('descript',300)->nullable();
            $table->integer('ordr')->unsigned()->nullable()->comment('примерный порядок работ');

            $table->decimal('plnvolqty',10,1)->nullable()->comment('Плановый объем работ, ЕИ');
            $table->bigInteger('unittypeid')->unsigned()->nullable()->comment('ID единицы измерения планируемого объема работ (по UnitTypes.id)');
		$table->foreign('unittypeid')->references('id')->on('unittypes');

            $table->date('plnbegdate')->nullable()->comment('планируемая дата начала работ');
            $table->decimal('plnworkhrs',10,1)->nullable()->comment('Плановая продолжительность работ, час');
            $table->decimal('plnbudgetsum',12,2)->nullable()->comment('Планируемый бюджет/полная стоимость выполнения вида работ, руб');

            $table->biginteger('contractid')->unsigned()->nullable()->comment('Договор с субподрядчиком работ');

		// нужно хранить в таблице с базовыми типами работ
            $table->string('optimal_beg_md',5)->nullable()->comment('рекомендуемый месяц/день начала работ');
            $table->string('optimal_end_md',5)->nullable()->comment('рекомендуемый месяц/день окончания работ');

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
        Schema::dropIfExists('buildopertypes');
    }
}
