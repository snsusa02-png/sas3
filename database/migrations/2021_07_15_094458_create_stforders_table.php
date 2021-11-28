<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStfordersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

/*
	"SALARY" NUMBER, 

	"EDOCID" CHAR(10), 
   ) ;
  ALTER TABLE "SNS"."STFORDERS" ADD CONSTRAINT "PK_STFORDERS_ID" PRIMARY KEY ("ID") ENABLE;
   COMMENT ON COLUMN "SNS"."STFORDERS"."ORDNUM" IS 'Номер приказа';
   COMMENT ON COLUMN "SNS"."STFORDERS"."ORDDATE" IS 'Дата приказа';
   COMMENT ON COLUMN "SNS"."STFORDERS"."BEGDATE" IS 'Начало действия';
   COMMENT ON COLUMN "SNS"."STFORDERS"."ENDDATE" IS 'Окончание действия';
   COMMENT ON COLUMN "SNS"."STFORDERS"."REASON" IS 'Основание приказа';
   COMMENT ON COLUMN "SNS"."STFORDERS"."SIGNER1ID" IS 'StaffID сотрудника, подписавшего приказ за руководителя предприятия';
   COMMENT ON COLUMN "SNS"."STFORDERS"."SIGNER2ID" IS 'StaffID сотрудника, подписавшего приказ за гл. бухгалтера';
   COMMENT ON COLUMN "SNS"."STFORDERS"."SIGNER3ID" IS 'StaffID сотрудника, согласовавшего приказ ';
   COMMENT ON COLUMN "SNS"."STFORDERS"."REMARK" IS 'Примечание, автоматически переносится из тип';
   COMMENT ON COLUMN "SNS"."STFORDERS"."SIGNER4ID" IS 'StaffID сотрудника, согласовавшего приказ. 2-й согласователь';
   COMMENT ON COLUMN "SNS"."STFORDERS"."WHNAPLD" IS 'Дата/время применения приказа в orgStaff';
   COMMENT ON COLUMN "SNS"."STFORDERS"."ORDPOINT" IS '№ пункта приказа - если есть несколько записей с одинаковым номером/датой, то их можно собрать в один при печати, упорядочив по номеру параграфа';
   COMMENT ON COLUMN "SNS"."STFORDERS"."ORDTYPEID" IS 'Тип приказа согласно справочника StfOrdTypes';

  CREATE INDEX "SNS"."STFORDERS_ORDTYPE" ON "SNS"."STFORDERS" ("ORDTYPE", "ENDDATE", "BEGDATE") 
  ;
  CREATE UNIQUE INDEX "SNS"."PK_STFORDERS_ID" ON "SNS"."STFORDERS" ("ID") 
  ;
  CREATE INDEX "SNS"."STFORDERS_STAFFID" ON "SNS"."STFORDERS" ("STAFFID") 
  ;
*/
        Schema::create('stforders', function (Blueprint $table) {
            $table->id();

            $table->biginteger('orgid')->unsigned()->comment('id организации выпустившей приказ (по orgs)');
	            $table->foreign('orgid')->references('id')->on('orgs');

            $table->biginteger('staffid')->unsigned()->comment('id сотрудника (по orgstaff)')->index();
	            $table->foreign('staffid')->references('id')->on('orgstaff');

            $table->biginteger('ordtypeid')->unsigned()->comment('id типа приказа (по stfordtypes)');
	            $table->foreign('ordtypeid')->references('id')->on('stfordtypes');

            $table->string('ordnum',16)->comment('Номер приказа');
            $table->date('orddate')->comment('дата приказа');
            $table->string('ordpoint',3)->nullable()->comment('№ пункта приказа(параграфа) - если есть несколько записей с одинаковым номером/датой, то их можно собрать в один при печати, упорядочив по номеру параграфа');

            $table->date('begdate')->comment('Начало действия приказа');
            $table->date('enddate')->nullable()->comment('Окончание действия');

            $table->string('reason',60)->nullable()->comment('Основание приказа');


            $table->biginteger('postid')->unsigned()->nullable()->comment('id должности (по orgposts)');
	            $table->foreign('postid')->references('id')->on('orgposts');

            $table->biginteger('depid')->unsigned()->nullable()->comment('id подраздаления (по orgdeps)');
	            $table->foreign('depid')->references('id')->on('orgdeps');

            $table->tinyinteger('salary_calctypeid')->unsigned()->nullable()->comment('Тип расчета ЗП: 1-за месяц, 2-за день, 3-за час');
            $table->decimal('salary',10,2)->nullable()->comment('Ставка ЗП за период, указанный в salary_calctypeid');


            $table->biginteger('signer1id')->unsigned()->nullable()
		->comment('StaffID сотрудника, подписавшего приказ за руководителя предприятия');
	            $table->foreign('signer1id')->references('id')->on('orgstaff');

            $table->biginteger('signer2id')->unsigned()->nullable()
		->comment('StaffID сотрудника, подписавшего приказ за главного бухгалтера');
	            $table->foreign('signer2id')->references('id')->on('orgstaff');

            $table->biginteger('signer3id')->unsigned()->nullable()
		->comment('StaffID сотрудника, согласовавшего приказ (ОК)');
	            $table->foreign('signer3id')->references('id')->on('orgstaff');

            $table->biginteger('signer4id')->unsigned()->nullable()
		->comment('StaffID сотрудника, согласовавшего приказ. 2-й согласователь');
	            $table->foreign('signer4id')->references('id')->on('orgstaff');

            $table->dateTime('applied_at')->nullable()
		->comment('Дата/время применения приказа в orgStaff. Признак примененности для "будущих" приказов');


            $table->string('notes',90)->nullable()->comment('Примечание');

            //$table->text('spec_data')->comment('json-представление специфических данных приказа ( в зависимости от типа)');


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
        Schema::dropIfExists('stforders');
    }
}
