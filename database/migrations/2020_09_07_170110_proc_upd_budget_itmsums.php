<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProcUpdBudgetItmsums extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
        DROP PROCEDURE IF EXISTS upd_budget_itmsums;

CREATE PROCEDURE `upd_budget_itmsums`(p_budgetid bigint)
BEGIN
       
	if(!isnull(p_budgetid)) then
    
		update `budget_itmsums` as s
		set 
			fctinpsum=(select sum(ifnull(opersum,0)) from budget_opers as o where o.itmsumid=s.id and dir=1 and o.stable),
			fctoutsum=(select sum(ifnull(opersum,0)) from budget_opers as o where o.itmsumid=s.id and dir=-1 and o.stable)
			where s.budgetid=p_budgetid;

		update `budget_itmsums` as s
			set fctsum=ifnull(fctinpsum,0)-ifnull(fctoutsum,0)
            where s.budgetid=p_budgetid;
   
    end if;

END

");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS upd_budget_itmsums');
    }
}
