<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSysobjs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sysobjs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parid')->unsigned()->nullable()->comment('ID родителя');

            $table->string('code',20)->unique('code');

            $table->string('name',60);
            $table->boolean('active')->default(1);
            $table->integer('ordr')->unsigned()->default(999999);
            $table->string('model_class',36)->nullable()->comment('название класса модели, без пути');

            $table->bigInteger('acl_sysobjid')->unsigned()->nullable()->comment('ID системы, по которой определяется доступ пользователей');
            $table->string('acl_sysobjcode',30)->nullable()->comment('Код системы, по которой определяется доступ пользователей');

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
        Schema::dropIfExists('sysobjs');
    }
}
