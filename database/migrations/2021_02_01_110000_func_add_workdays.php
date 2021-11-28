<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FuncaddWorkdays extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
        drop function if exists add_workdays; 


CREATE FUNCTION `add_workdays`(v_begdate date, v_workdays integer) RETURNS date
    READS SQL DATA
BEGIN
	declare v_enddate date;
	declare v_date date;
	declare m_adddays integer;

    -- учтем, что в полученном периоде могут быть выходные дни. Тогда конечную дату нужно увеличить на число этих выходных дней
	set m_adddays = v_workdays;
    set v_date = v_begdate;
	-- insert into temp_log (info) values (concat('begdate= ',v_date));
	
    set v_enddate = date_add(v_date, interval m_adddays day);
    -- insert into temp_log (info) values (concat('first enddate= ',v_enddate));
    
    REPEAT

		-- подсчитаем кол-во выходных дней, приходящихся на период v_date - v_enddate
		select count(*) into m_adddays 
            from calendar_dates where calendarid=1 and workhrs = 0 and date between date_add(v_date, interval 1 day) and v_enddate;
		-- insert into temp_log (info) values (concat('m_auxdays= ', m_adddays, ' for period: ', v_date,' - ',v_enddate));

		if (m_adddays>0) then
			-- если были выходные - добавим
			-- set v_date = date_add(v_enddate, interval 1 day);	-- с
            set v_date = v_enddate;
        	set v_enddate = date_add(v_date, interval m_adddays day);
        end if;
		-- insert into temp_log (info) values (concat(' new period: ', v_date,' - ',v_enddate));

    UNTIL m_adddays=0
	END REPEAT;
    -- insert into temp_log (info) values (concat('enddate= ',v_enddate));

	RETURN v_enddate;
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
        DB::unprepared("drop function if exists add_workdays;");
    }
}
