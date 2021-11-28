<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ProcitGetOrdr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
        DROP PROCEDURE IF EXISTS it_getOrdr;
        
CREATE PROCEDURE `it_getOrdr`(IN p_itmid INT UNSIGNED, OUT return_path TEXT)
BEGIN
    DECLARE m_parent_id INT UNSIGNED;
    DECLARE path_result TEXT;
    SET max_sp_recursion_depth=50;

    SELECT CONCAT( ifnull(ac.ordr,99),'|'), ac.parent_id 
		INTO return_path, m_parent_id 
        FROM itmtypes AS ac 
       WHERE ac.id = p_itmid;
    
    IF m_parent_id > 0 THEN
        CALL it_getOrdr(m_parent_id, path_result);
        SELECT CONCAT(path_result, return_path) INTO return_path;
    END IF;
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
        DB::unprepared('DROP PROCEDURE IF EXISTS it_getOrdr');
    }
}
