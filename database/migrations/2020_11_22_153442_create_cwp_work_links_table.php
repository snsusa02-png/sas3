<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCwpWorkLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cwp_work_links', function (Blueprint $table) {
            $table->id();

            $table->biginteger('src_workid')->unsigned()->index();
		$table->foreign('src_workid')->references('id')->on('cwp_works');

            $table->biginteger('tgt_workid')->unsigned()->index();
		$table->foreign('tgt_workid')->references('id')->on('cwp_works')->onDelete('cascade');

//            $table->string('basefield',3)->default('END')->comment('Опорное поле даты из src-записи: BEG/END');
//            $table->decimal('hr_shift',6,1)->default(0)->comment('Смещение в часах');

            $table->decimal('src_beg_shift',6,1)->nullable()->default(0)->comment('Смещение (в часах) поставки от начала src_workid');
		// или
            $table->decimal('src_end_shift',6,1)->nullable()->comment('Смещение (в часах) поставки от окончания src_workid');

//            $table->string('setfield',3)->default('BEG')->comment('Устанавливаемое поле даты в tgt-записи: BEG/END');


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
        Schema::dropIfExists('cwp_work_links');
    }
}
