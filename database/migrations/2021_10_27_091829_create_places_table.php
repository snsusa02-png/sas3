<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();

            $table->string('code',16)->nullable();
            $table->string('name',60)->nullable();
            $table->string('descript',360)->nullable();
            $table->string('address',90)->nullable();

            $table->tinyinteger('for_load')->default(1)->comment('Признак места погрузки');
            $table->tinyinteger('for_unload')->default(0)->comment('Признак места разгрузки');

            $table->tinyinteger('active')->default(1)->comment('');


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
        Schema::dropIfExists('places');
    }
}
