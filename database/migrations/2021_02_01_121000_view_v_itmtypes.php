<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ViewVItmtypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("DROP VIEW IF EXISTS v_itmtypes;");

        DB::unprepared(
          "
CREATE 
    ALGORITHM = UNDEFINED 
    SQL SECURITY DEFINER
VIEW `v_itmtypes` AS
    SELECT 
        `it`.`id` AS `id`,
        `it`.`parent_id` AS `parent_id`,
        `it`.`name` AS `name`,
        `it`.`descript` AS `descript`,
        `it`.`isservice` AS `isservice`,
        `it`.`active` AS `active`,
        `it`.`ordr` AS `ordr`,
        `it`.`photourl` AS `photourl`,
        `it`.`created_at` AS `created_at`,
        `it`.`created_by` AS `created_by`,
        `it`.`updated_at` AS `updated_at`,
        `it`.`updated_by` AS `updated_by`,
        IT_PATH(`it`.`id`) AS `name_path`,
        IT_ORDR(`it`.`id`) AS `ordr_path`
    FROM
        `itmtypes` `it`
    ORDER BY IT_ORDR(`it`.`id`)
	"
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared("DROP VIEW IF EXISTS v_itmtypes;");
    }
}
