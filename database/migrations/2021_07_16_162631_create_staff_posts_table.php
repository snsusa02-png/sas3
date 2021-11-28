<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff_posts', function (Blueprint $table) {
            $table->id();

            $table->biginteger('staffid')->unsigned()->comment('id сотрудника (по orgstaff)')->index();
	            $table->foreign('staffid')->references('id')->on('orgstaff');

            $table->biginteger('depid')->unsigned()->nullable()->comment('id подразделения (orgdeps)');
	            $table->foreign('depid')->references('id')->on('orgdeps');
            $table->biginteger('postid')->unsigned()->nullable()->comment('id должности (orgposts)');
	            $table->foreign('postid')->references('id')->on('orgposts');
            $table->string('postname',60)->nullable()->comment('Название должности');


            $table->date('begdate')->comment('дата вступления в должность');
            $table->date('enddate')->nullable()->comment('дата оставления должности');

            $table->string('reason',60)->nullable()->comment('Основание');

            $table->biginteger('stforderid')->unsigned()->nullable()->comment('id приказа-основания (stforders)');
	            $table->foreign('stforderid')->references('id')->on('stforders');

//            $table->tinyinteger('salary_calctypeid')->unsigned()->nullable()->comment('Тип расчета ЗП: 1-за месяц, 2-за день, 3-за час');
//            $table->decimal('salary',10,2)->nullable()->comment('Ставка ЗП за период, указанный в salary_calctypeid');

            $table->decimal('jobfraction',4,2)->nullable()->default(1)->comment('Ставка');

            $table->string('notes',90)->nullable()->comment('Примечание');

            $table->boolean('active')->default(true);


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
        Schema::dropIfExists('staff_posts');
    }
}
