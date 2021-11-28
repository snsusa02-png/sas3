<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_acs', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('userid')->unsigned();
//		$table->foreign('userid')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('acsid')->unsigned();
		$table->foreign('acsid')->references('id')->on('acs')->onDelete('cascade');

            $table->boolean('active')->default(1);

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
        Schema::dropIfExists('user_acs');
    }
}
