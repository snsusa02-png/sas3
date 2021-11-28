<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjmsgRcptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('objmsg_rcpts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('objmsgid')->unsigned()->index('objmsgid');
		$table->foreign('objmsgid')->references('id')->on('obj_msgs')
		->onDelete('cascade');

            $table->bigInteger('rcptuserid')->unsigned()->default(1)
                ->index('rcptuserid')
                ->comment('UserID пользователя кому адресовано сообщение');
		$table->foreign('rcptuserid')->references('id')->on('users');

            $table->timestamp('read_at')->nullable()->comment('Время ознакомления с сообщением');

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
        Schema::dropIfExists('objmsg_rcpts');
    }
}
