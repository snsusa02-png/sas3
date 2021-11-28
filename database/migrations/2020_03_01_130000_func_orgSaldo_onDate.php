<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FuncOrgSaldoOnDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
        drop function if exists orgSaldo_onDate; 

	CREATE FUNCTION `orgSaldo_onDate`(
		p_OrgID BIGINT
		, p_OwnOrgID bigint
		, p_viewDate date
	) RETURNS double
	    READS SQL DATA
	    COMMENT 'Рассчитывает сальдо контрагента на заданную дату'

	BEGIN
		DECLARE m_Rslt double;
		declare m_MinDate date;
		declare m_Saldo double;
		declare m_OperSum double;
        
		SELECT saldo, ondate
		into m_Saldo, m_MinDate
		FROM org_saldos
		where ownorgid=p_OwnOrgID 
			and orgid=p_OrgID
		        and active=1
			and ondate<=p_viewDate
		order by ondate desc limit 1;
    
		set m_MinDate = ifnull(m_MinDate, '2018-01-01');
		set m_Rslt = ifnull(m_Saldo, 0);
	
		-- insert into temp_log (info) values (m_Rslt);

		select sum(a.sum) as sum
		    into m_OperSum
		    from (
			SELECT 
				SUM(pd.paydir*pd.paysum) as sum
			FROM paydocs AS pd
			WHERE
				pd.orgid = p_OrgID
				and pd.ownorgid = p_OwnOrgID
				and pd.docdate between m_MinDate and p_ViewDate
        			and pd.stable=1

			union all
			SELECT 
				SUM(-iq.qty*oi.price) as sum
			FROM orditems AS oi
			join orders as o 
			  on o.id=oi.ordid
			join oi_qtys as iq
			  on iq.oiid=oi.id and iq.stageid=2
			WHERE
				 o.orgid = p_OrgID
				 and o.ownorgid=p_OwnOrgID
				 and o.orddate between m_MinDate and p_ViewDate
			) as a;

			-- insert into temp_log (info) values (m_OperSum);
    
			set m_Rslt = m_Rslt + ifnull( m_OperSum, 0);
            
			RETURN round(m_Rslt,2);
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
        DB::unprepared("drop function if exists orgSaldo_onDate;");
    }
}
