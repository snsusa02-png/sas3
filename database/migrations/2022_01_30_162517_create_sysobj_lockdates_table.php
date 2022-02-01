<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSysobjLockdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sysobj_lockdates', function (Blueprint $table) {

            $table->bigInteger('sysobjid')->unsigned()->primary();
		$table->foreign('sysobjid')->references('id')->on('sysobjs');

            $table->date('lock_before')->comment('Данные системы sysobjid заблокированы до(!) этой даты');

            $table->timestamp('updated_at')->nullable()->useCurrent = true;
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
        Schema::dropIfExists('sysobj_lockdates');
    }
}
