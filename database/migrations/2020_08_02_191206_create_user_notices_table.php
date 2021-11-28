<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserNoticesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_notices', function (Blueprint $table) {
            $table->id();
//            $table->biginteger('from_userid')->unsigned()->nullable()->index()->comment('id пользователя-автора');
//		$table->foreign('from_userid')->references('id')->on('users');

            $table->biginteger('to_userid')->unsigned()->nullable()->index()->comment('id пользователя-получателя');
		$table->foreign('to_userid')->references('id')->on('users');

		$table->unsignedBigInteger('eventtypeid')->nullable();
//		      $table->foreign('eventtypeid')->references('id')->on('eventtypes')->onDelete('cascade');

		$table->unsignedBigInteger('ref_sysobjid')->nullable();
		      $table->foreign('ref_sysobjid')->references('id')->on('sysobjs')
			->onDelete('cascade');
	        $table->unsignedBigInteger('ref_objid')->nullable();

            $table->string('subj',160)->nullable()->comment('Тема сообщения');
            $table->string('msg',300)->nullable()->comment('Текст сообщения');
            $table->string('ref_url',160)->nullable()->comment('ссылка на исходное место');

            //$table->timestamp('until_dt')->nullable()->comment('актуально до указанной даты/времени');
            $table->timestamp('begdt')->nullable()->comment('уведомлять начиная с указанной даты/времени');
            $table->timestamp('enddt')->nullable()->comment('уведомлять до указанной даты/времени');

            $table->boolean('active')->default(true);

            $table->timestamp('readed_at')->nullable();

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');


            $table->index(['ref_url','eventtypeid','to_userid']);
            $table->index(['ref_sysobjid','ref_objid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_notices');
    }
}
