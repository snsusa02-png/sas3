<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWrhdocnumsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wrhdocnums', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('doctypeid')->unsigned()->index('doctypeid')
                ->comment('ID типа документа (по WrhDocTypes.ID)');
            $table->tinyInteger('ownorgid')->unsigned()->index('ownorgid')
                ->comment('ID организации-продавца (по orgs.ID)');

            $table->boolean('active')->default(1);
            $table->datetime('begdt')
                ->comment('Начало периода использования')
                ->useCurrent = true;
            $table->datetime('enddt')->nullable()
                ->comment('Окончание периода использования');

            $table->Integer('nxtnum')->unsigned()
                ->default(1)
                ->comment('Следующий свободный номер документа');

            $table->string('numfmt',36)->nullable()
                ->comment('шаблон формата номера документа. Например: <num>-<yyyy>');

            $table->string('numagain', 1)
                  ->nullable()
                  ->default('Y')
                  ->comment('периодичность возобновления нумерации документов (с 1). 
                    null - не возобновлять; Y - каждый год');

            $table->timestamp('created_at')->nullable()->useCurrent = true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
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
        Schema::dropIfExists('wrhdocnums');
    }
}
