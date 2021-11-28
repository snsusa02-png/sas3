<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateObjprefsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('objprefs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('sysobjid')->unsigned()->nullable()->index('sysobjid');
            $table->bigInteger('objid')->unsigned()->nullable()->index('objid');
            $table->bigInteger('preftypeid')->unsigned()->index('preftypeid');

            $table->string('prefvalue', 300)->comment('Значение преференции для отображения. Совпадает со строковым значением');
            $table->decimal('n_val', 12,3)->nullable()->comment('Числовое значение преференции');
            $table->dateTime('d_val')->nullable()->comment('Значение преференции для Даты/Времени');

            $table->timestamp('created_at')->nullable()->useCurrent = true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');

            $table->unique(['sysobjid', 'objid', 'preftypeid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('objprefs');
    }
}
