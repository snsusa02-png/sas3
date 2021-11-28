<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjMsgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obj_msgs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('replyto_id')->unsigned()->nullable()->index();
		$table->foreign('replyto_id')->references('id')->on('obj_msgs')
		->onDelete('cascade');

            $table->bigInteger('sysobjid')->unsigned()->index('sysobjid');
		$table->foreign('sysobjid')->references('id')->on('sysobjs');

            $table->bigInteger('objid')->unsigned()->nullable()->index('objid');
            //Если objid is null, то считаем, что сообщение имеет смысл для всех objid,
            // указанного sysobjid

            $table->bigInteger('from_userid')->nullable()->unsigned()->default(1)
                ->comment('UserID, отправившего сообщение');
            $table->timestamp('sent_at')->nullable()->comment('Время отправки');

            $table->string('subj', 160)->comment('Тема сообщения');
	    $table->text('body')->nullable()->comment('Текст сообщения');

            $table->boolean('private')->default(0)->comment('0-общедоступно; 1-приватное, только для автора и получателей');

            $table->boolean('active')->default(1)->comment('0-черновик; 1-отображать');

//            $table->dateTime('viewbegdt')
//                ->comment('Начало периода отображения')
//                ->useCurrent = true;
//            $table->dateTime('viewenddt')->nullable()
//                ->comment('Окончание периода отображения');


           $table->timestamp('created_at')->nullable()->useCurrent = true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
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
        Schema::dropIfExists('obj_msgs');
    }
}
