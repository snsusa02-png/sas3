<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSrsHrItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('srs_hr_items', function (Blueprint $table) {
            $table->id();
            $table->biginteger('srs_id')->unsigned()->comment('ID набора ставок');
		$table->foreign('srs_id')->references('id')->on('salary_rate_sets');

            $table->biginteger('wrktypeid')->unsigned()->comment('Тип работы');
	            $table->foreign('wrktypeid')->references('id')->on('wrktypes');

            $table->decimal('min_wrkexp',4,1)->comment('Минимальный стаж работы в организации (>=)');
            $table->decimal('max_wrkexp',4,1)->nullable()->comment('Максимальный стаж работы в организации (<)');

            $table->decimal('hr_day_rate',10,2)->nullable()->comment('Ставка за час работы в дневную смену, руб');
            $table->decimal('hr_night_rate',10,2)->nullable()->comment('Ставка за час работы в дневную смену, руб');

            $table->decimal('hr_aux_rate',10,2)->nullable()->comment('Ставка за час работы с доп. оборудованием (с прицепом), руб');

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
        Schema::dropIfExists('srs_hr_items');
    }
}
