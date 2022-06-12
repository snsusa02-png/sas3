<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ProcRecalcStock2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tCode = 'DROP PROCEDURE IF EXISTS recalc_stock;';
        $tCode = $tCode . ' CREATE PROCEDURE recalc_stock() BEGIN ';
        $tCode = $tCode . ' truncate table wrh_stocks;';
        $tCode = $tCode . ' insert into wrh_stocks (ownorgid, wrhid, boxid, sysobjid, objid, grpid, refitmid, qty, plnincqty, plnoutqty)';
        $tCode = $tCode . ' SELECT d.ownorgid, d.wrhid, d.boxid, d.sysobjid, d.objid, d.grpid, dl.refitmid';
        $tCode = $tCode . ', SUM(ifnull(dlt.forstock,t.forstock) * dl.qty * IF(d.docsigned=1,1,0)) Qty';
        $tCode = $tCode . ', SUM(ifnull(dlt.forstock,t.forstock) * dl.qty * IF(d.docsigned <> 1 AND ifnull(dlt.forstock,t.forstock) = 1, 1, 0)) PlnIncQty';
        $tCode = $tCode . ', SUM(ifnull(dlt.forstock,t.forstock) * dl.qty * IF(d.docsigned <> 1 AND ifnull(dlt.forstock,t.forstock) = -1, -1, 0)) PlnOutQty';
        $tCode = $tCode . ' FROM wrhdoclst AS dl';
        $tCode = $tCode . ' JOIN wrhdocs d ON d.id = dl.docid';
        $tCode = $tCode . ' JOIN wrhdoctypes t ON t.id = d.doctypeid';
        $tCode = $tCode . ' left JOIN wrhdoctypes dlt ON dlt.id = dl.subtypeid ';
        $tCode = $tCode . ' where d.wrhid is not null';
        $tCode = $tCode . ' GROUP BY d.ownorgid, d.wrhid, d.boxid, d.sysobjid, d.objid, d.grpid, dl.refitmid;';
        $tCode = $tCode . ' END';
        DB::unprepared($tCode);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
