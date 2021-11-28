<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obj_approvals', function (Blueprint $table) {
            $table->id();

            $table->biginteger('sysobjid')->unsigned()->nullable();
	        $table->foreign('sysobjid')->references('id')->on('sysobjs')->onDelete('cascade')->onUpdate('cascade');

            $table->biginteger('objid')->unsigned()->nullable();

            $table->string('obj_hash',60)->nullable()->comment('Хэш значимых полей объекта');

            $table->biginteger('stageid')->unsigned()->comment('Решение для этапа');

            $table->biginteger('dcsn_rightid')->unsigned()->nullable()->comment('ID права, неоходимого пользователю для принятия решения');
            $table->string('dcsn_typecode',36)->nullable()->comment('Код типа решения');

            $table->biginteger('dcsn_orgid')->unsigned()->nullable();
	        $table->foreign('dcsn_orgid')->references('id')->on('orgs');

            $table->biginteger('dcsn_userid')->unsigned()->nullable();
	        $table->foreign('dcsn_userid')->references('id')->on('users');
            $table->biginteger('dcsn_staffid')->unsigned()->nullable();
	        $table->foreign('dcsn_staffid')->references('id')->on('orgstaff');

            $table->boolean('decision')->nullable();
            $table->dateTime('dcsn_at')->nullable();
            $table->string('descript',300)->nullable();

            $table->boolean('active')->default(0);

            $table->timestamps();

		$table->index(['sysobjid', 'objid','stageid'], 'approvals_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('obj_approvals');
    }
}
