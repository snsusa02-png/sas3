<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ViewVWrhs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared(
            "
            DROP VIEW IF EXISTS v_wrhs;
            
            CREATE 
                ALGORITHM = UNDEFINED 
                SQL SECURITY DEFINER
            VIEW `v_wrhs` AS
                SELECT 
                    `w`.`id` AS `id`,
                    `w`.`name` AS `name`,
                    `w`.`descript` AS `descript`,
                    `w`.`address` AS `address`,
                    `w`.`active` AS `active`,
                    `w`.`created_at` AS `created_at`,
                    `w`.`created_by` AS `created_by`,
                    `w`.`updated_at` AS `updated_at`,
                    `w`.`updated_by` AS `updated_by`,
                    IFNULL(CONCAT(`u1`.`lname`, ' ', `u1`.`fname`),
                            `u1`.`name`) AS `created_by_name`,
                    IFNULL(CONCAT(`u2`.`lname`, ' ', `u2`.`fname`),
                            `u2`.`name`) AS `updated_by_name`
                FROM
                    ((`wrhs` `w`
                    LEFT JOIN `users` `u1` ON ((`u1`.`id` = `w`.`created_by`)))
                    LEFT JOIN `users` `u2` ON ((`u2`.`id` = `w`.`updated_by`)))
        ");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared("DROP VIEW IF EXISTS v_wrhs;");
    }
}
