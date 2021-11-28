<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ProcGenCrudSysfuncs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
        DROP PROCEDURE IF EXISTS gen_crud_sysfuncs;
        
CREATE PROCEDURE `gen_crud_sysfuncs`(p_sysobjid bigint)
    MODIFIES SQL DATA
    COMMENT 'создание отсутствующих записей в sysfuncs для указанного  p_sysobjid'
BEGIN
	
		declare m_sysobjcode varchar(60);
		declare f_eof int;
		declare m_code varchar(60);
        declare m_cnt int;
		declare m_subcode varchar(30);
        declare m_name varchar(200);
        declare m_ordr varchar(200);
        
        declare basefuncs cursor for
			select 'read' as subcode, 'Чтение записей' as name, 1 as ordr
			union
			select 'create' as subcode, 'Создание записей' as name, 2 as ordr
			union
			select 'update' as subcode, 'Изменение записей' as name, 3 as ordr
			union
			select 'delete' as subcode, 'Удаление записей' as name, 4 as ordr;

		DECLARE CONTINUE HANDLER FOR NOT FOUND SET f_eof= 1;


		SELECT code INTO m_sysobjcode 
			FROM sysobjs
			WHERE id = p_sysobjid;

        if(m_sysobjcode is not null) then

			open basefuncs;
			L1: loop
			
				FETCH basefuncs into m_subcode, m_name, m_ordr;
				
				set m_code=concat(m_sysobjcode, '.', m_subcode);
				SELECT COUNT(*) iNTO m_cnt FROM  sysfuncs AS sf
					WHERE sf.sysobjid = p_sysobjid 
					and sf.code=m_code COLLATE 'utf8mb4_general_ci';
				if (m_cnt=0) then
					insert into sysfuncs (sysobjid, code, name, adminrightid, ordr)
					values (p_sysobjid, m_code, m_name,1,m_ordr);
				end if;

				if(f_eof) then 
					leave L1; 
				end if;
			end loop L1;
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
        DB::unprepared('DROP PROCEDURE IF EXISTS createuserstaffid');
    }
}
