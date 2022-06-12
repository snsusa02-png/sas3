<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FuncMayunsignwrhdoc extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
            drop function if exists MayUnsignWrhDoc;

            CREATE FUNCTION `MayUnsignWrhDoc`(p_DocID bigint) RETURNS int(11)
            READS SQL DATA
            BEGIN
            declare m_wrhid bigint;
            declare m_forstock integer;
            declare cnt integer;
            
            select t.forstock, d.wrhid into m_forstock, m_wrhid 
                from wrhdocs as d
                join wrhdoctypes as t
                  on t.id=d.doctypeid where d.id=p_DocID;
                  
            if (m_forstock>0) then
                #документ связан с поступлением на склад, => его отмена может привести к снижению запасов ниже 0
                select count(*) into cnt
                  from (
                        select refitmid, sum(qty) as qty 
                          from( select refitmid, qty from wrh_stocks s
                                where s.wrhid=m_wrhid
                                union
                                select refitmid, sum(-qty) as qty 
                                  from wrhdoclst as dl 
                                 where docid=p_DocID 
                                 group by refitmid) as a
                        group by refitmid) as b
                where b.qty<0;
            else
                    set cnt = 0;
            end if;
            RETURN (cnt=0);
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
        DB::unprepared("
            drop function if exists MayUnsignWrhDoc;");
    }
}
