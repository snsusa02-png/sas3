<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAclRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_acl_roles', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('userid')->unsigned()->comment('ID пользователя');
		$table->foreign('userid')->references('id')->on('users')->onDelete('cascade');

            $table->bigInteger('roleid')->unsigned()->comment('ID роли доступа');
		$table->foreign('roleid')->references('id')->on('acl_roles')->onDelete('cascade');

            $table->boolean('active')->default(1);

            $table->string('reason', 60)->nullable();

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('userid, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('userid, изменившего запись');

            $table->index(['userid','roleid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_acl_roles');
    }
}
