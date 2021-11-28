<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obj_comments', function (Blueprint $table) {
            $table->id();

		$table->unsignedBigInteger('sysobjid');
		      $table->foreign('sysobjid')->references('id')->on('sysobjs');
	        $table->unsignedBigInteger('objid');

	        $table->unsignedBigInteger('from_userid');
		$table->foreign('from_userid')
		        ->references('id')->on('users')
		        ->onDelete('cascade');

	        $table->unsignedBigInteger('replyto_commentid')->nullable();
		$table->foreign('replyto_commentid')
		        ->references('id')->on('obj_comments')
		        ->onDelete('cascade');

	      $table->text('body');

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->timestamp('updated_at')->nullable()->useCurrent=true;

            $table->index(['sysobjid','objid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('obj_comments');
    }
}
