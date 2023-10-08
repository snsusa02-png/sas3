<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMchnSpareUsagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mchn_spare_usages', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('machineid')->unsigned()->nullable()->index('machineid');
		$table->foreign('machineid')->references('id')->on('machines');

            $table->date('operdate')->default(date("Y-m-d"))->comment('Дата установки/использования');

	    $table->string('spare_name', 60)->nullable()->comment('Название детали');
	    $table->decimal('price', 12,2)->nullable()->comment('Цена 1 детали');
	    $table->decimal('qty', 6)->nullable()->comment('Кол-во деталей');
	    $table->decimal('spare_sum', 12,2)->comment('Сумма установленных деталей');

            $table->string('notes',160)->nullable();

            $table->boolean('active')->default(1)
                ->comment('1-признак активности, 0 - черновик');

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
        Schema::dropIfExists('mchn_spare_usages');
    }
}
