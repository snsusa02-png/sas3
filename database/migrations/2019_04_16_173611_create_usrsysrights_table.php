<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsrsysrightsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Schema::dropIfExists('usrsysrights');

        Schema::create('usrsysrights', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->bigInteger('userid')->unsigned()->comment('ID пользователя');
		$table->foreign('userid')->references('id')->on('users')->onDelete('cascade');

            $table->bigInteger('sysfuncid')->unsigned()->comment('ID права/функции');
		$table->foreign('sysfuncid')->references('id')->on('sysfuncs')->onDelete('cascade');


            $table->bigInteger('limsysobjid')->unsigned()->nullable()->comment('в пределах объектов типа sysobjid');
		$table->foreign('limsysobjid')->references('id')->on('sysobjs')->onDelete('cascade');

            $table->bigInteger('limobjid')->unsigned()->nullable()->comment('в пределах экземпляра объекта типа sysobjid');

            $table->dateTime('begdt')->useCurrent=true;
            $table->dateTime('enddt')->nullable();
            $table->boolean('active')->default(1);

            $table->string('reason',30)->nullable();

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('userid, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('userid, изменившего запись');

            $table->index(['userid','sysfuncid']);

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
    }
}
