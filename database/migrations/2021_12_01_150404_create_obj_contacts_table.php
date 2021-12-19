<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obj_contacts', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('sysobjid')->unsigned();
		$table->foreign('sysobjid')->references('id')->on('sysobjs');

            $table->bigInteger('objid')->unsigned();

            $table->bigInteger('contacttypeid')->unsigned()->nullable()->comment('ID типа контакта: 1-phone, 2-email, 3-web-site, ...');
            $table->string('contact', 90)->comment('Данные контакта');
            $table->string('notes', 120)->nullable()->comment('Примечание');

            $table->boolean('private')->default(0)->comment('0-общедоступно; 1-приватное, только для автора и получателей');

            $table->boolean('active')->default(1)->comment('0-черновик; 1-отображать');


            $table->timestamp('created_at')->nullable()->useCurrent = true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');

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
        Schema::dropIfExists('obj_contacts');
    }
}
