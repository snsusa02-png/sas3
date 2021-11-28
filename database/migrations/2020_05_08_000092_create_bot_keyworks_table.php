<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotKeyworksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot_keyworks', function (Blueprint $table) {
            $table->id();

	    $table->biginteger('buildopertypeid')->unsigned()->index();
		$table->foreign('buildopertypeid')->references('id')->on('buildopertypes');

            $table->string('name',60);
            $table->string('descript',160)->nullable();

            $table->decimal('plnqty',12,3)->nullable()->comment('Плановое (целевое) кол-во, ЕИ');
            $table->biginteger('unittypeid')->unsigned()->index()->nullable();
		$table->foreign('unittypeid')->references('id')->on('unittypes');

            $table->dateTime('plnbegdt')->nullable();
            $table->dateTime('plnenddt')->nullable();

            $table->integer('ordr')->unsigned()->nullable()->default(999)->comment('примерный порядок вывода');
            $table->boolean('active')->nullable()->default(1);

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
        Schema::dropIfExists('bot_keyworks');
    }
}
