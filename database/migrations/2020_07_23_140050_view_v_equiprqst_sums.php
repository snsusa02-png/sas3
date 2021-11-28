<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ViewVEquipRqstSums extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Schema::dropIfExists('v_equiprqst_sums');
        DB::unprepared("DROP VIEW IF EXISTS v_equiprqst_sums;");

        DB::unprepared(
          "
          CREATE
                ALGORITHM = UNDEFINED
                SQL SECURITY DEFINER
            VIEW `v_equiprqst_sums` AS
SELECT 
        `ri`.`rqstid` AS `rqstid`,
        SUM(`ri`.`rqst_qty`) AS `rqst_qty`,
        SUM(IFNULL(`ri`.`ord_qty`, 0)) AS `ord_qty`,
        SUM(IFNULL(`ri`.`ord_sum`, 0)) AS `ord_sum`,
        SUM(`ri`.`get_qty`) AS `get_qty`,
        SUM(`ri`.`m15_qty`) AS `m15_qty`
    FROM
        `equiprqst_items` `ri`
    GROUP BY `ri`.`rqstid`
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
        DB::unprepared("DROP VIEW IF EXISTS v_equiprqst_sums;");
    }
}
