<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsrsysrightOrgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('usrsysright_orgs', function (Blueprint $table) {
            $table->id();
            
            $table->bigInteger('usrsysrightid')->unsigned();
	      $table->foreign('usrsysrightid')->references('id')->on('usrsysrights')
		->onDelete('cascade');

            $table->bigInteger('orgid')->unsigned()->nullable()->comment('ограничение права объектами указанной orgID');
	      $table->foreign('orgid')->references('id')->on('orgs')
		->onDelete('cascade');

            $table->boolean('active')->nullable()->default(1);

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('userid, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('userid, изменившего запись');

            $table->index(['usrsysrightid','orgid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('usrsysright_orgs');
    }
}
