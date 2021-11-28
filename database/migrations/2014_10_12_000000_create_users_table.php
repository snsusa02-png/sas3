<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            $table->string('lname',16)->nullable();
            $table->string('fname',16)->nullable();
            $table->string('mname',16)->nullable();
            $table->string('phone',20)->nullable();
            $table->string("note",256)->nullable();

            $table->date("birthdate")->nullable()->comment('Дата рождения');
            $table->string("sex",1)->nullable()->comment('Пол: M-муж, F-жен');

            $table->biginteger('roleid')->nullable()->comment('ID роли пользователя (по UserRoleTypes.ID)');
            $table->string('profile_image')->nullable()->comment('profile image field');
            $table->bigInteger('curorgid')
                    ->nullable()
                    ->unsigned()
                    ->comment('текущая представляемая организация');
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
        Schema::dropIfExists('org_curators');
        Schema::dropIfExists('users');
    }
}
