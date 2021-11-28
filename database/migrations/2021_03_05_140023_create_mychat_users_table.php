<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMychatUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mychat_users', function (Blueprint $table) {
            $table->id();
            $table->biginteger('userid')->unsigned()->index()->comment('id пользователя');
		$table->foreign('userid')->references('id')->on('users');

            $table->integer('uin')->comment('UIN пользователя в MyChat');


//            $table->string('subj', 160)->nullable()->comment('Краткое описание');

            $table->boolean('active')->default(true);


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
        Schema::dropIfExists('mychat_users');
    }
}
