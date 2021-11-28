<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class sysfiletypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sysfiletypes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',256);
            $table->string('description',512)->nullable();
            $table->bigInteger('mimetype_id')->unsigned()->nullable();
            $table->tinyInteger('direct')->default(0)->comment('Направление файла -1 - экпорт, 1 - импорт,  0 - направления');
            $table->boolean('active')->default(1);
            $table->string('datamodel',32)->nullable()->comment('Код модели данных (для xml-файлов)');
            $table->string('storage',256)->comment('Хранилище');
            $table->string('catalog',64)->comment('Каталог хранилища');
            $table->string('handler',512)->nullable()->comment('Маршрут для загрузки');

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
        Schema::dropIfExists('sysfiletypes');
    }
}
