<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjMailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proj_mails', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('projid')->unsigned()->comment('id проекта');
		$table->foreign('projid')->references('id')->on('projects')->onDelete('cascade');

            $table->biginteger('mailboxid')->unsigned();
//		$table->foreign('mailboxid')->references('id')->on('proj_mailboxes');

            $table->biginteger('uid')->unsigned();

            $table->string('mail_from', 60)->nullable()->comment('');
            $table->string('mail_to', 60)->nullable()->comment('');
            $table->string('subject', 360)->nullable()->comment('');
            $table->text('text_body')->nullable()->comment('');
            $table->text('html_body')->nullable()->comment('');

            $table->tinyInteger('attachment_cnt')->nullable()->unsigned()
		->comment('');

            $table->biginteger('srcorgid')->unsigned()->nullable();

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1);
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1);


            $table->index(['mailboxid','uid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('proj_mails');
    }
}
