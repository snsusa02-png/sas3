<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSysfuncs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sysfuncs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('code',30)->nullable()->unique()->comment("мнемо-код");
            $table->bigInteger('sysobjid')->unsigned()->index('sysobjid')->comment('для какого объекта');

            $table->string('name',60);
            $table->bigInteger('adminrightid')->unsigned()->comment('Право для установки данного права');
            $table->boolean('org_area')->default(0)->comment('Признак возможности разделения права по объектам организаций');
            $table->boolean('active')->default(1);
            $table->tinyinteger('ordr')->unsigned()->default(255);

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
        Schema::dropIfExists('usrsysrights');
        Schema::dropIfExists('stfsysrights');

        Schema::dropIfExists('sysfuncs');
    }
}
