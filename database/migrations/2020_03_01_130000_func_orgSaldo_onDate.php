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
		declare m_viewDate date;
		declare m_MinDate date;
		declare m_Saldo double;
		declare m_OperSum double;
        
        set m_viewDate = ifnull(p_viewDate, curdate());
        -- insert into temp_log (info) values (m_viewDate);
		SELECT 
			saldo, ondate
		INTO m_Saldo, m_MinDate 
		FROM org_saldos
		WHERE
			ownorgid = p_OwnOrgID
				AND orgid = p_OrgID
				AND active = 1
				AND ondate <= m_viewDate
		ORDER BY ondate DESC
		LIMIT 1;

		-- insert into temp_log (info) values (m_MinDate);
    
		set m_MinDate = ifnull(m_MinDate, '2018-01-01');
		set m_Rslt = ifnull(m_Saldo, 0);
	
		-- insert into temp_log (info) values (m_MinDate);
		SELECT SUM(a.sum) AS sum INTO m_OperSum 
			FROM (
				SELECT SUM(-opersum) AS sum
					FROM obj_finopers AS fo
					WHERE srcorgid = p_OwnOrgID and tgtorgid = p_OrgID
						AND fo.operdate BETWEEN m_MinDate AND m_viewDate
                union all        
				SELECT SUM(opersum) AS sum
					FROM obj_finopers AS fo
					WHERE srcorgid = p_OrgID and tgtorgid = p_OwnOrgID
						AND fo.operdate BETWEEN m_MinDate AND m_viewDate
				 ) AS a;

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
