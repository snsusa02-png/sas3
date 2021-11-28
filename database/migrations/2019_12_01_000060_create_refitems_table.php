<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefitemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::dropIfExists('refitems');

        Schema::create('refitems', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->unsigned()->nullable()->index('parent_id');

            $table->string('code', 30)->nullable()->comment('уникальный Код товара');
	            $table->unique('code');

            $table->string('partnumber', 24)->nullable()->comment('Код товара/Номер по каталогу производителя');

            $table->string('name', 300);

//            $table->boolean('has_child')
//                    ->default(0)
//                    ->comment('0-нет дочерних записей(опций/вариантов); 1-есть');
//
//            $table->boolean('has_detail')
//                    ->default(0)
//                    ->comment('0-не составная запись; 1-составная (есть записи в ri_details)');
//            $table->boolean('is_make_in')
//                    ->default(0)
//                    ->comment('1-можно собрать из материалов/услуг; 0-нельзя');
//            $table->boolean('is_make_out')
//                    ->default(0)
//                    ->comment('1-можно разобрать/разкомплектовать на товары; 0-нельзя');

            $table->tinyInteger('producttypeid')->unsigned()->nullable()->comment('1 = услуга,  2 = материал,  3 = оборудование');
            $table->tinyInteger('qty_dec_digits')->unsigned()->default(0)->nullable()
                    ->comment('Кол-во знаков после запятой при учете количества');

            $table->decimal('costprice',12,2)->nullable()->comment('Себестоимость товара за единицу, руб');
            $table->decimal('retailprice', 12, 2)->nullable()->comment('Рекомендованная розничная цена производителя');
            $table->decimal('price', 12, 2)->nullable()->comment('Текущая отпускная цена (базовая для наценок/скидок)');

            $table->bigInteger('unittypeid')->unsigned()->nullable()->comment('ID единицы измерения (по UnitTypes.id)');
            $table->string('unit', 16)->nullable()->default('шт')->comment('Единица измерения');

//            $table->bigInteger('baseunittypeid')->unsigned()->nullable()->comment('ID базовой единицы измерения (из родительской записи. Поэтому наверное не нужна!)');
//            $table->decimal('bu_qty', 9, 3)->nullable()->default(1)->comment('Кол-во базовых единиц в unittypeid для этого товара');

            $table->decimal('grossweight', 12, 3)->nullable()->comment('вес брутто единицы товара, кг');

            $table->date('salebegdate')->default(date("Y-m-d"))
                ->comment('Дата начала продаж');
            $table->date('saleenddate')->nullable()->comment('Дата окончания продаж (глобально)');

            $table->boolean('active')->default(1);

            $table->string('manufacturer', 120)->nullable()->comment('Производитель');
            $table->bigInteger('brandid')->nullable()->unsigned()->index('brandid')->comment('по brands.id');
            $table->string('brand', 120)->nullable()->comment('Бренд');
            $table->text('descript')->nullable();
            $table->string('photourl', 90)->nullable();
            $table->tinyInteger('procstatus_id')->nullable();


            $table->bigInteger('itmtypeid')->unsigned()->nullable()
		->index('itmtypeid')->comment('Категория "по-умолчанию" - для облегчения аналитических запросов');

            $table->bigInteger('bdgtacnttypeid')->unsigned()->nullable()->comment('ID типа статьи бюджета');
	            $table->foreign('bdgtacnttypeid')->references('id')->on('bdgtacnttypes')->onDelete('set null');


            $table->string('altname',60)->nullable()
                    ->comment('псевдоним, альтернативное наименование');

            $table->string('searchname',360)->nullable()
                    ->comment('поисковая форма наименования. Вычисляется(!)');

            $table->Integer('ordr')
		    ->unsigned()
                    ->nullable()
 		    ->default(999999999)
                    ->comment('порядок вывода. 1-100 - отведены для ручного приоритета, 101 и более - авто приоритет (от популярности в продажах)');

            $table->timestamp('created_at')->nullable()->useCurrent = true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent = true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');

            //
 
           //FK
            $table->foreign('parent_id')->references('id')->on('refitems');
//            $table->foreign('producttypeid')->references('id')->on('producttypes');

//            $table->foreign('ItmTypeID')->references('id')->on('itmtypes');
//            $table->foreign('ItmSubTypeID')->references('id')->on('itmsubtypes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::dropIfExists('findocitems');

//        Schema::dropIfExists('orditems');
//        Schema::dropIfExists('orditmgroups');

//        Schema::dropIfExists('ri_files');
//        Schema::dropIfExists('ri_stocks');
//        Schema::dropIfExists('ri_itmtypes');

//        Schema::dropIfExists('ri_details');
//        Schema::dropIfExists('riopt_qtymarkups');
//        Schema::dropIfExists('ri_options');

//        Schema::dropIfExists('ri_units');
//        Schema::dropIfExists('ri_altnames');

	Schema::dropIfExists('refitems');
    }
}
