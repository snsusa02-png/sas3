<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItmtypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('itmtypes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->unsigned()->nullable()->index('parent_id');

            $table->string('name',60);
            $table->string('descript',300)->nullable();
            $table->boolean('isservice')
                    ->default(0)
                    ->comment('0-товар; 1-услуга');
            $table->boolean('active')->default(1);

            $table->tinyInteger('ordr')->unsigned()->nullable();
            $table->string('photourl',60)->nullable();

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
        Schema::dropIfExists('it_si_links');
        Schema::dropIfExists('ri_itmtypes');
        Schema::dropIfExists('itmtypes');
    }
}
