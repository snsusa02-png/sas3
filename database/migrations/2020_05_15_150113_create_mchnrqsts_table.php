<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMchnrqstsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mchnrqsts', function (Blueprint $table) {
            $table->id();

            $table->biginteger('rqsttypeid')->unsigned()->nullable()->comment('тип заявки по mchnrqsttypes.id');

            $table->tinyinteger('statusid')->unsigned()->nullable()->default(0)
		->comment('0-черновик; 1-ожидает согласовния; 2-согласована; 3-отклонена; 9-завершена');


            $table->biginteger('rqstmchntypeid')->unsigned()->nullable()->comment('по machntypes.id');
            $table->string('mchnrequirements',160)->nullable()->comment('требования к технике');
            $table->biginteger('rqstmachineid')->unsigned()->nullable()->comment('Запрошенный экземпляр техники');

            $table->string('name',160)->nullable();

            $table->biginteger('buildobjid')->unsigned()->nullable()->index()->comment('Объект ID по BuildObjs.ID');
            $table->biginteger('buildopertypeid')->unsigned()->nullable()->comment('Вид работ объекта по BuildOperTypes.ID');
                                    
            $table->biginteger('ownorgid')->unsigned()->nullable()->index();
            $table->biginteger('orgid')->unsigned()->nullable()->index();
            $table->biginteger('contractid')->unsigned()->nullable()->index()->comment('Договор для расценок');

            $table->string('contactname',90)->nullable()->comment('ФИО ответственного лица Заказчика');
            $table->string('contactphone',30)->nullable()->comment('телефон ответственного лица Заказчика');

            $table->dateTime('plnbegdt')->nullable();
            $table->dateTime('plnenddt')->nullable();

	    //3-перевозка грузов
            $table->biginteger('src_orgid')->unsigned()->nullable()->index()->comment('Грузоотправитель');
            $table->biginteger('tgt_orgid')->unsigned()->nullable()->index()->comment('Грузополучатель');

            $table->string('cargo_boxcnt',60)->nullable()->comment('количество грузовых мест, маркировка, вид тары и способ упаковки');
            $table->string('src_addr',160)->nullable()->comment('адрес места погрузки');
            $table->biginteger('src_placeid')->unsigned()->nullable()->comment('ID объекта/места/локации - где нужно загрузить груз');
            $table->string('tgt_addr',160)->nullable()->comment('для спецтехники - адрес проведения работ, для грузоперевозки - адрес места выгрузки');
            $table->biginteger('tgt_placeid')->unsigned()->nullable()->comment('ID объекта/места/локации - куда нужно доставить груз');

            $table->string('descript',300)->nullable()->comment('Доп. сведения - Отгрузочное наименование груза / Описание работ,
                                            дополнительные сведения');

            $table->string('notes',300)->nullable()->comment('Примечание');

            $table->boolean('active')->default(true)->comment('0-черновик; 1-подана');
            $table->boolean('offbalance')->default(false)->comment('0-официально, входит в отчетность; 1-фиктивный док-т');

            $table->biginteger('inituserid')->unsigned()->nullable()->index()->comment('Инициатор');
            $table->timestamp('init_at')->nullable()->comment('Время подачи заявки');
            
	    //Согласование заявки ------------------
            $table->boolean('decision')->nullable()->comment('null-решение не принято; 0-отказ; 1-согласована');
            $table->biginteger('dcsn_userid')->unsigned()->nullable()->comment('Пользователь утвердивший заявку/отказавший в заявке');
            $table->string('dcsn_descript',200)->nullable()->comment('Пояснение к решению о согласовании');
            $table->timestamp('dcsn_at')->nullable()->comment('Время принятия решения');

            $table->biginteger('car_orgid')->unsigned()->nullable()->index()->comment('Грузоперевозчик');
            $table->boolean('isowncarrier')->nullable()->comment('0-сторонний перевозчик; 1-перевозчик из холдинга');
            $table->biginteger('asgnmachineid')->unsigned()->nullable()->index()->comment('Назначенный (свой) экземпляр спецтехники');
            $table->string('asgnmachinename',160)->nullable()->comment('Название/описание назначенной техники - для стороннего перевозчика');

            $table->tinyinteger('paytypeid')->unsigned()->nullable()->default(1)->comment('1-Б/Н, 2-Нал');

            $table->biginteger('wrkorgid')->unsigned()->nullable()->comment('Организация-подрядчик');
            $table->biginteger('wrkcontractid')->unsigned()->nullable()->comment('Договор с организацией-подрядчиком/Договор-бюджета');
            $table->biginteger('bgitmsumid')->unsigned()->nullable()->index()->comment('Статья бюджета');
            $table->decimal('plnwrkhrs', 6,1)->nullable()->default(0)->comment('Планируемое кол-во работы техники');

	    //Регистрация фаетических данных по исполнению заявки
            $table->dateTime('fctbegdt')->nullable();
            $table->dateTime('fctenddt')->nullable();

            $table->biginteger('fct_userid')->unsigned()->nullable()->index()->comment('Регистратор фактических данных');
            $table->timestamp('fct_at')->nullable()->comment('Время регистрации факта');

            $table->decimal('fcthrs', 6,1)->nullable()->comment('фактически отработано кол-во часов - устарело, заменим на fct_qty');
            $table->string('fct_priceunit',36)->nullable()->default('час')->comment('В чем измеряется фактическое количество');
            $table->decimal('fct_qty', 6,1)->nullable()->comment('фактически отработано кол-во (fct_priceunit)');
	    $table->decimal('fct_price',12,2)->nullable()->comment('Цена за fct_priceunit от фактического перевозчика');
	    $table->decimal('fct_sum',12,2)->nullable()->comment('Общая стоимость от фактического перевозчика');

            $table->string('drivername',60)->nullable()->comment('ФИО водителя');
            $table->biginteger('driver_orgid')->unsigned()->nullable()->index()->comment('организация водителя (ИП)');
            $table->biginteger('driver_contractid')->unsigned()->nullable()->index()->comment('id договора с перевозчиком (источник цен для driver_price/driver_sum)');
	    $table->decimal('driver_price', 12,2)->nullable()->comment('Цена от перевозчика (ИП по договору id=1) для заказчика-посредника');
	    $table->decimal('driver_sum', 12,2)->nullable()->comment('Общая стоимость для заказчика-посредника от организации-перевозчика car_orgid');

            $table->decimal('add_vatrate', 6,1)->nullable()->default(0)->comment('Возрастание факт. цены на ставку НДС');
            $table->decimal('add_markup', 6,1)->nullable()->default(0)->comment('Возрастание факт. цены на интерес организатора');
	    $table->decimal('own_price', 12,2)->nullable()->comment('Цена для заказчика = fct_price * (100+add_vatrate+add_markup)/100');
	    $table->decimal('own_sum', 12,2)->nullable()->comment('Общая стоимость для заказчика от ownorgid');

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
        Schema::dropIfExists('mchnrqsts');
    }
}
