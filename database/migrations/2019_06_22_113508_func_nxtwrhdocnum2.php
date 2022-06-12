<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FuncNxtwrhdocnum2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
            drop function if exists NxtWrhDocNum;

            CREATE FUNCTION `nxtwrhdocnum`(p_doctypeid bigint, p_ownorgid bigint, p_docdt datetime) RETURNS varchar(16) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci
            READS SQL DATA
            BEGIN
            declare o_num varchar(16);
            declare m_active boolean;
            declare m_num integer;
            declare m_id bigint;
            declare m_numfmt varchar(36);
            declare m_enddt, m_lastdt datetime;
            declare m_numAgain char(1);
            
            #insert into temp_log (info) values (p_docdt);
            IF isnull(p_docdt) THEN 
                SET p_docdt = now(); 
            END IF;
             
            select id, active, nxtnum, numfmt, numagain into m_id, m_active, m_num, m_numfmt, m_numAgain
            from wrhdocnums as n
            where n.doctypeid=p_doctypeid
            and n.ownorgid=p_ownorgid
            and p_docdt between n.begdt and ifnull(n.enddt, p_docdt) 
            limit 1;
            
            if (ifnull(m_active,1)=1) then
            # если записи нет, или она есть и активна, то возвращаем номер
            
                if (isnull(m_num)) then
                    set m_num = 1;
            
                    if isnull(m_numfmt) then
                       set m_numfmt = '<n>';
                    end if;
            
                    if m_numAgain = 'Y' then
                        SELECT date_sub(date_add(last_day(concat(extract(year from p_docdt),'-12-1')) , interval 1 day), interval 1 second) into m_lastdt;
                    elseif m_numAgain = 'M' then
                        SELECT date_sub(date_add(last_day(p_docdt), interval 1 day), interval 1 second) into m_lastdt;
                    elseif m_numAgain = 'D' then
                        SELECT date_sub(date_add(date(now()), interval 1 day), interval 1 second) into m_lastdt;
                    else
                        set m_lastdt = null;
                    end if;
                    
                    #определим, возможно уже есть 'сосед сверху' - ограничим срок использования
                    select DATE_SUB(begdt, INTERVAL 1 SECOND)  into m_enddt
                        from wrhdocnums as n
                        where n.doctypeid=p_doctypeid
                        and n.ownorgid=p_ownorgid
                        and p_docdt < n.begdt
                        order by n.begdt
                        limit 1;
                    
                    if not isnull(m_enddt) then    
                        set m_enddt = least(m_enddt, m_lastdt);
                    else
                        set m_enddt = m_lastdt;
                    end if;
                        
                    insert into wrhdocnums (doctypeid, ownorgid, nxtnum, numfmt, begdt, enddt) 
                    values (p_doctypeid, p_ownorgid, 2, m_numfmt, p_docdt, m_enddt);
                else
                        update wrhdocnums set nxtnum = nxtnum+1
                        where id=m_id;
                end if;
                if not isnull(m_numfmt) then
                    set o_num = m_numfmt;
                    set o_num = replace(o_num, '<n>', m_num);
                    set o_num = replace(o_num, '<yyyy>', EXTRACT(YEAR FROM p_docdt));
                    set o_num = replace(o_num, '<yy>', LPAD(substring(extract(YEAR FROM p_docdt),3,2),2,'0'));
                    set o_num = replace(o_num, '<mm>', lpad(EXTRACT(month FROM p_docdt),2,'0'));
                    set o_num = replace(o_num, '<dd>', lpad(EXTRACT(day FROM p_docdt),2,'0'));
                else    
                    set o_num = m_num;
                end if;
            
            else 
                #m_active =0 - запись неактивна, вернем нулл, как признак временной блокировки генерации новых номеров
                set o_num = null;
            end if;
            
            RETURN o_num;
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
            drop function if exists NxtWrhDocNum;");
    }
}
