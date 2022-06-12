<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWrhdoctypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wrhdoctypes', function (Blueprint $table) {
            $table->bigIncrements('id');

                $table->bigInteger('parent_id')
                    ->unsigned()
                    ->nullable()
                    ->comment('ID родительского типа документа');

            $table->string('name',50)->comment('Название типа документа');

            $table->tinyInteger('forstock')->default(0)->comment('Влияние на запас на складе 0-не влияет, 1-увеличивает, -1-уменьшает');
            $table->tinyInteger('forsale')->default(0)->comment('Влияние на взаиморасчеты между поставщиком/покупателем "0"-не влияет, "1"-наша продажа, "-1"-наша покупка');

            $table->boolean('useprice')->default(0)->comment('Отображать/Редактировать цену в документе. 0-нет, 1-да');
            $table->boolean('any_ownorg')->default(0)->comment('1-Можно брать товар из запаса другого владельца');

            $table->boolean('active')->default(1);

            $table->unsignedTinyInteger('ordr')->default(254)->comment('порядок вывода в списках');

                $table->boolean('need_org')
                    ->default(0)
                    ->comment('1-требуется указание контрагента');

                $table->boolean('need_relwrh')
                    ->default(0)
                    ->comment('1-требуется указание второго (связанного) склада');

                $table->boolean('need_predoc')
                    ->default(0)
                    ->comment('1-требуется указание документа-предка');

                $table->bigInteger('predoctypeid')
                    ->unsigned()
                    ->nullable()
                    ->comment('ID необходимого типа документа-предка');

                $table->bigInteger('chlddoctypeid')
                    ->unsigned()
                    ->nullable()
                    ->comment('ID необходимого типа документа-последователя');

                $table->bigInteger('need_respstaffid')
                    ->unsigned()
                    ->nullable()
                    ->default(0)
                    ->comment('Требуется указать ID ответственного сотрудника');

                $table->bigInteger('signrightid')
                    ->unsigned()
                    ->nullable()
                    ->comment('ID права пользоавателя необходимого для согласования документов этого типа');

                $table->boolean('not_gt_preqty')
                    ->default(0)
                    ->comment('1-кол-во товара в строке не может превышать кол-во в соотв. строке предшествующего документе');

                $table->string('ownorg_label',30)
                    ->nullable()
                    ->default('Владелец')
                    ->comment('Этикета для поля "OwnOrgID"');

                $table->string('wrh_label',30)
                    ->nullable()
                    ->comment('Этикета для поля "Склад"');

                $table->string('relwrh_label',30)
                    ->nullable()
                    ->comment('Этикета для поля "Связанный склад"');

                $table->string('box_label',30)
                    ->nullable()
                    ->comment('Этикета для поля "Отделение склада"');

                $table->string('relbox_label',30)
                    ->nullable()
                    ->comment('Этикета для поля "Отделение связанного склада"');

                $table->boolean('lst_editable')
                    ->nullable()
                    ->comment('1-состав документа можно редактировать');

                $table->boolean('need_details')
                    ->nullable()
                    ->comment('1-в составе документа могут быть указаны только товары, имеющие спецификацию');


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
        Schema::dropIfExists('wrhdoctypes');
    }
}
