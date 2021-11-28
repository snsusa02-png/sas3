<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FuncitOrdr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
        drop function if exists it_ordr; 


CREATE FUNCTION `it_ordr`(p_itmid bigINT) RETURNS bigint
    DETERMINISTIC
BEGIN
    DECLARE res TEXT;
    declare lvl int;
    declare ordr int;
    declare part TEXT;
    declare i int;
    
    CALL it_getOrdr(p_itmid, res);
	
    
    set lvl = 0;
    set ordr = 0;
    SELECT position('|' in res) INTO i;
    while (i>0) do
		set lvl = lvl+2;
    
		select substr(res, 1, i-1) into part;
        set ordr = ordr + power(10, 8-lvl)*convert(part, UNSIGNED INTEGER);
        
		set res = substr(res, i+1);
        SELECT position('|' in res) INTO i;
    end while;
    RETURN ordr;
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
        DB::unprepared("drop function if exists it_ordr;");
    }
}
