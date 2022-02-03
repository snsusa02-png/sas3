<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractrolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contractroles', function (Blueprint $table) {
            $table->id();
            $table->biginteger('contracttypeid')->unsigned()->index()->comment('по contracttypes.id');

            $table->string('name',120);
            $table->string('descript',200)->nullable();
            $table->tinyInteger('party')->unsigned()->default(2);
            $table->integer('ordr')->unsigned()->default(999999);
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
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('contractroles');
    }
}
