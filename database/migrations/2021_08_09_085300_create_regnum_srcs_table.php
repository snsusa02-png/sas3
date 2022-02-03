<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegnumSrcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('regnum_srcs', function (Blueprint $table) {
            $table->id();

            $table->string('code',6)->unique('code')->nullable();
            $table->string('name',60);
            $table->string('descript',160)->nullable();

	    $table->bigInteger('ownorgid')->unsigned()->index('suporgid');
	            $table->foreign('ownorgid')->references('id')->on('orgs')->onDelete('cascade');

	    $table->bigInteger('contract_categoryid')->unsigned()->nullable()->index('contract_categoryid')
		->comment('привязка к категории. null - подходит для всех');
	            $table->foreign('contract_categoryid')->references('id')->on('contract_categories')
			->onDelete('cascade');

            $table->string('num_prefix',8)->nullable();
            $table->Integer('nextnum')->unsigned()->default(1)->comment('Текущее значение номера');
            $table->string('num_suffix',8)->nullable();

            $table->boolean('active')->default(1)->comment('1-используется');

            $table->integer('ordr')->unsigned()->nullable()->default(0)->comment('примерный порядок');

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
        Schema::dropIfExists('regnum_srcs');
    }
}
