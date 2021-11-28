<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaxsystemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxsystems', function (Blueprint $table) {
            //Системы налогообложения юр.лиц
            $table->tinyIncrements('id');

            $table->string('code', 10)->comment('');
            $table->string('name', 40)->comment('Название системы налогообложения');
            $table->string('vatrate', 4)
                ->comment('Ставка НДС по-умолчанию. "0", "10", "18", "20", "-" - Без НДС');

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
        Schema::dropIfExists('taxsystems');
    }
}
