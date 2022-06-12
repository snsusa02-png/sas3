<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWrhdocsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Документы учета товара
        Schema::create('wrhdocs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('predocid')->unsigned()->nullable()->index('predocid')
                ->comment('ID связанного документа (предшественника). Предполагается использовать для внутренних перемещений между складами');

            $table->bigInteger('src_sysobjid')->unsigned()->nullable()
                ->comment('ID системного справочника для документа-основания');
            $table->bigInteger('src_objid')->unsigned()->nullable()
                ->comment('ID записи в справочнике (src_sysobjid) на документ-основание');

            $table->bigInteger('ordid')->unsigned()->nullable()->index('ordid')
                ->comment('ID связанного заказа (есть не у всех)');
            $table->bigInteger('sysobjid')->unsigned()->nullable()
                ->comment('ID системного справочника для привязки');
            $table->bigInteger('objid')->unsigned()->nullable()
                ->comment('ID записи в справочнике (src_sysobjid) для привязки');
            $table->tinyInteger('grpid')->unsigned()->nullable()
                ->comment('ID "кучки". 1-исходный товар, 3-переработанный(подготовленный) товар');

            $table->bigInteger('doctypeid')->unsigned()->index('doctypeid');

            $table->string('docnum',16)->nullable()->comment('Номер документа');
            $table->date('docdate')->nullable()->comment('Дата документа');
            $table->decimal('docsum',12,2)->nullable()->comment('Сумма документа. Эффективная Сумма = docsum * docSigned');

            $table->bigInteger('ownorgid')->unsigned()->nullable()->index('ownorgid');
            $table->bigInteger('orgid')->unsigned()->nullable()->index('orgid');

            $table->bigInteger('wrhid')->unsigned()->nullable()->index('wrhid');
            $table->bigInteger('relwrhid')->unsigned()->nullable()->index('relwrhid');

            $table->bigInteger('boxid')->unsigned()->nullable()->index('boxid');
            $table->bigInteger('relboxid')->unsigned()->nullable()->index('relboxid');

            $table->bigInteger('srcboxid')->unsigned()->nullable()->index('srcboxid');
	        $table->foreign('srcboxid')->references('id')->on('wrh_boxes');
            $table->bigInteger('tgtboxid')->unsigned()->nullable()->index('tgtboxid');
	        $table->foreign('tgtboxid')->references('id')->on('wrh_boxes');

            $table->bigInteger('respuserid')
                  ->unsigned()
                  ->nullable()
                  ->comment('ID персоны, ответственной за документ (недостачу). Можно редактировать при непустом составе документа');

            $table->bigInteger('respstaffid')
                  ->unsigned()
                  ->nullable()
                  ->comment('(устарело?) ID сотрудника, ответственного за документ (недостачу). Можно редактировать при непустом составе документа');

            $table->string('remarks',60)->nullable()->comment('Примечания к документу');

            $table->boolean('docsigned')->default(0)->comment('1-признак подписанности, 0 - черновик');

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');


		$table->index(['src_sysobjid','src_objid']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wrhdocs');
    }
}
