<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjStagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obj_stages', function (Blueprint $table) {
            $table->id();
            $table->biginteger('sysobjid')->unsigned()->nullable();
	        $table->foreign('sysobjid')->references('id')->on('sysobjs')->onDelete('cascade')->onUpdate('cascade');

            $table->biginteger('code')->unsigned()->nullable();

            $table->string('name',60);
            $table->string('descript',160)->nullable();
            $table->integer('ordr')->unsigned()->default(999);

            $table->string('css')->nullable()->comment('Стиль');

            $table->boolean('active')->default(true);

            $table->biginteger('prevstage_code')->unsigned()->nullable();
            $table->biginteger('nextstage_code')->unsigned()->nullable();
            $table->biginteger('cnclstage_code')->unsigned()->nullable()->comment('Переход при отказе');

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');
//            $table->timestamps();

		$table->unique(['sysobjid', 'code'], 'stages_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('obj_stages');
    }
}
