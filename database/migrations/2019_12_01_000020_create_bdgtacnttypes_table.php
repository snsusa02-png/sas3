<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBdgtacnttypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bdgtacnttypes', function (Blueprint $table) {
            $table->id();

            $table->string('name',160)->comment('Наименовние статьи расхода/дохода');

 	    $table->tinyinteger('dir')->default(0)->comment('+1 - статья прихода, -1 - статья расхода');

            $table->boolean('no_correction')->default(false)->comment('1-не применять коэффициент снижения');
            $table->boolean('for_equiprqst')->default(false)->comment('1-используется для заявок на материалы');
            $table->integer('ordr')->unsigned()->nullable()->default(0)->comment('примерный порядок');
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
        Schema::dropIfExists('bdgtacnttypes');
    }
}
