<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obj_links', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('sysobjid')->unsigned()->comment('связь с sysobjs.id');
		$table->foreign('sysobjid')->references('id')->on('sysobjs');
            $table->bigInteger('objid')->unsigned()->comment('связь c записью в конкретной таблице');
            $table->string('objrolename',60)->nullable()->comment('роль объекта в связи');

            $table->bigInteger('lnksysobjid')->unsigned()->comment('связь с sysobjs.id');
		$table->foreign('lnksysobjid')->references('id')->on('sysobjs');
            $table->bigInteger('lnkobjid')->unsigned()->comment('связь c записью в конкретной таблице');
            $table->string('lnkobjrolename',60)->nullable()->comment('роль связанного объекта в связи');

            $table->bigInteger('linktypeid')->unsigned()->comment('тип связи');
//		$table->foreign('linktypeid')->references('id')->on('objlinktypes');

            $table->string('name',90)->nullable()->comment('Пояснение связи, роль');
            $table->string('show_url',90)->nullable()->comment('ссылка на связанную запись');


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
        Schema::dropIfExists('obj_links');
    }
}
