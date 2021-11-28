<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewRefitemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('new_refitems', function (Blueprint $table) {
            $table->id();

            $table->string('code',20)->nullable()->comment('Код');
            $table->string('name',160)->comment('Название');
            $table->bigInteger('unittypeid')->unsigned()->nullable()->comment('ID единицы измерения (по UnitTypes.id)');
            $table->string('unit',20)->comment('ЕИ');
            $table->decimal('qty',12,3)->nullable()->comment('Цена');
            $table->decimal('price',10,2)->nullable()->comment('Цена');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('new_refitems');
    }
}
