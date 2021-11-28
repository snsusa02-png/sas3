<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFlagtypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('flagtypes', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('code',20)->nullable()->unique()
                    ->comment('уникальный код флага');

            $table->string('name',160);

            $table->string('remarks',60)->nullable()->comment('Примечания');

            $table->bigInteger('forsysobjid')->unsigned()->nullable()->comment('Применимо для объектов этого типа');

            $table->boolean('active')->nullable()->default(1);

            $table->boolean('uservisible')->nullable()->default(1);
            $table->boolean('clientvisible')->nullable()->default(1);
            $table->string('css_style')->nullable()->comment('Стиль');

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('StaffID, изменившего запись');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('flagtypes');
    }
}
