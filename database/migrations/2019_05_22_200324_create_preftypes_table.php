<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePreftypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('preftypes', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name', 65)->comment('Название преференции');

            $table->string('valtype', 1)->comment('C/N/D');
            $table->string('valsrctype', 3)->comment('USR/LST/SQL');
            $table->string('valsrcdef', 300)->nullable()->comment('Описание договора');
            $table->tinyInteger('valmaxlen')->nullable()->unsigned();
            $table->Integer('nvalmin')->nullable()->comment('минимальное значение для числового значения преференции');
            $table->Integer('nvalmax')->nullable()->comment('максимальное значение для числового значения преференции');

            $table->bigInteger('needrightid')->nullable()->unsigned();

            $table->boolean('active')->default(1)
                ->comment('1-используется, 0 - недоступна');

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
        Schema::dropIfExists('preftypes');
    }
}
