<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExtsysSysobjsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('extsys_sysobjs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('extsysid')->unsigned()->index('extsysid')
		->comment('ID внешней системы по extsystems.ID');

            $table->bigInteger('sysobjid')->unsigned()->index('sysobjid')
		->comment('ID типа объекта по sysobjs.ID');

            $table->boolean('active')->default(true);

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');
	
            $table->foreign('extsysid')->references('id')->on('extsystems')
	        ->onDelete('cascade');
            $table->foreign('sysobjid')->references('id')->on('sysobjs')
	        ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('extsys_sysobjs');
    }
}
