<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAclRoleRightsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acl_role_rights', function (Blueprint $table) {
            $table->id();

            $table->biginteger('roleid')->unsigned()->nullable()->comment('Роль доступа');
		$table->foreign('roleid')->references('id')->on('acl_roles');

            $table->biginteger('sysfuncid')->unsigned()->comment('Право доступа');
		$table->foreign('sysfuncid')->references('id')->on('sysfuncs');

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
        Schema::dropIfExists('acl_role_rights');
    }
}
