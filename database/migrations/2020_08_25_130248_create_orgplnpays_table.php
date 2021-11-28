<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrgplnpaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orgplnpays', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('ownorgid')->unsigned();
		$table->foreign('ownorgid')->references('id')->on('orgs')->comment('Владелец');

            $table->bigInteger('orgacntid')->nullable()->unsigned();
		$table->foreign('orgacntid')->references('id')->on('org_acnts')->comment('р/счет');

            $table->bigInteger('inituserid')->nullable()->unsigned();
		$table->foreign('inituserid')->references('id')->on('users')->comment('Инициатор');

            $table->date('docdate')->nullable()->comment('Дата документа');

            $table->decimal('restbegsum',12,2)->nullable()->comment('Остаток на р/счете на на начало даты docdate');
            $table->decimal('plnpaysum',12,2)->nullable()->comment('Планируемая сумма платежей');
            $table->decimal('aprvpaysum',12,2)->nullable()->comment('Согласованная сумма платежей');
            $table->decimal('restendsum',12,2)->nullable()->comment('Остаток на р/счете на на окончание даты docdate');

            $table->string('notes',160)->nullable()->comment('Примечания');

            $table->boolean('active')->default(true);
            $table->boolean('locked')->default(false)->comment('Блокирован от изменений');

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
        Schema::dropIfExists('orgplnpays');
    }
}
