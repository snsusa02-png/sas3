<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAssistantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_assistants', function (Blueprint $table) {
            $table->id();

            $table->biginteger('userid')->unsigned()->nullable()->index()->comment('id пользователя, имеющего помощников');
		$table->foreign('userid')->references('id')->on('users');

            $table->biginteger('assistant_userid')->unsigned()->nullable()->index()->comment('id пользователя-помощника');
		$table->foreign('assistant_userid')->references('id')->on('users');

		$table->unsignedBigInteger('for_sysobjid')->nullable()->comment('система где делегированы полномочия');
		      $table->foreign('for_sysobjid')->references('id')->on('sysobjs')
			->onDelete('cascade');

            $table->string('notes',160)->nullable()->comment('Пояснения');

            $table->datetime('begdt')->nullable()->comment('начиная с указанной даты/времени');
            $table->datetime('enddt')->nullable()->comment('до указанной даты/времени');

            $table->boolean('active')->default(true);

            $table->integer('ordr')->unsigned()->default(999);


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
        Schema::dropIfExists('user_assistants');
    }
}
