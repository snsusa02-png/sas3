<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInformersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('informers', function (Blueprint $table) {
            $table->id();

            $table->string('code',6)->unique('code')->nullable();
            $table->string('name',60);
            $table->string('descript',360)->nullable();

            $table->biginteger('acsid')->unsigned()->default(1)->index()->comment('категория информации - для доступа');
            $table->boolean('public')->default(true)->comment('0-приватный, 1-публичный');
            $table->boolean('active')->default(1)->comment('1-используется');

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
        Schema::dropIfExists('informers');
    }
}
