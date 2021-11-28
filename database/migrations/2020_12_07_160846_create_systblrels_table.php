<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystblrelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('systblrels', function (Blueprint $table) {
            $table->id();

            $table->string('srctbl',16)->comment('таблица источник');
            $table->string('srcfld',16)->comment('ключ поле');

            $table->string('tgttbl',16)->comment('дочерняя таблица');
            $table->string('tgtfld',16)->comment('ключ поле связи в дочерней таблице');
            $table->string('fltcond',60)->nullable()->comment('доп. условие связи');


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
        Schema::dropIfExists('systblrels');
    }
}
