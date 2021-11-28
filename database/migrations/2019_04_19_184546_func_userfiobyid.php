<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FuncUserfiobyid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
        DROP function IF EXISTS userfiobyid;
        
        CREATE FUNCTION `userfiobyid`(p_UserID bigint) RETURNS varchar(120) CHARSET utf8mb4
            READS SQL DATA
            COMMENT 'Получение ФИО пользователя'
        BEGIN
        DECLARE m_FIO VARCHAR(120);
        SELECT 
            ifnull( CONCAT(u.lname, ' ', u.fname, ' ', u.mname), u.name)
        INTO m_FIO 
            from users AS u
        WHERE
            u.id = p_UserID;
           RETURN trim(m_FIO);
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
        DB::unprepared("DROP function IF EXISTS userfiobyid;");
    }
}
