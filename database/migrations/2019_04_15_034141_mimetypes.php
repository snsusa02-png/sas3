<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Mimetypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mimetypes', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('name',256);
        $table->string('description',512)->nullable();
        $table->string('extension',10)->nullable()->comment('Расширение имени файла для типа');
        $table->string('mimetype',128)->nullable()->comment('MIME type (lowercased)');
        $table->string('readclass',512)->nullable()->comment('Класс для открытия файла'); ;
        $table->string('iconfile',128)->nullable()->comment('файл с иконкой типа'); ;

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
        Schema::dropIfExists('mimetypes');
    }
}
