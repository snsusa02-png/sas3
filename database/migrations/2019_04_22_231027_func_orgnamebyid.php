<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FuncOrgnamebyid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
        drop function if exists orgnamebyid; 
        CREATE FUNCTION `orgnamebyid`(`p_OrgID` BIGINT) 
            RETURNS varchar(120) CHARSET utf8mb4
            READS SQL DATA
            COMMENT 'Получение Названия компании'
        BEGIN
            DECLARE m_Name VARCHAR(120);
            select name into m_Name from orgs where id=p_OrgID;
            RETURN m_Name;
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
        DB::unprepared("drop function if exists orgnamebyid;");
    }
}
