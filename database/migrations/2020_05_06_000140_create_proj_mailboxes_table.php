<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjMailboxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proj_mailboxes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('projid')->unsigned()->comment('id проекта');
//		$table->foreign('projid')->references('id')->on('projects')->onDelete('cascade');

            $table->string('name',120);

            $table->string('imap_host',36)->default('imap.yandex.com');
            $table->integer('imap_port')->default(933);
            $table->string('imap_protocol',16)->default('imap');
            $table->string('imap_encryption',16)->default('ssl');
            $table->string('imap_validate_cert',16)->default('true');
            $table->string('imap_authentication',16)->default('null')->nullable();
            $table->string('imap_default_account',36)->default('default');

            $table->string('imap_username',36);
            $table->string('imap_password',16);

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
        Schema::dropIfExists('proj_mailboxes');
    }
}
