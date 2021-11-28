<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FuncitPath extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
        drop function if exists it_path; 


CREATE FUNCTION `it_path`(p_itmid bigINT) RETURNS text CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci
    DETERMINISTIC
BEGIN
    DECLARE res TEXT;
    CALL it_getPath(p_itmid, res);
    RETURN res;
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
        DB::unprepared("drop function if exists it_path;");
    }
}
