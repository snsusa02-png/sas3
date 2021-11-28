<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProcAddRiEstpricesFromOffers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
        DROP PROCEDURE IF EXISTS add_ri_est_prices_from_offers;
        

	CREATE PROCEDURE `add_ri_est_prices_from_offers`()
	    MODIFIES SQL DATA
	    COMMENT 'добавляет оценку стоимости товара из спр-ка номенклатуры по предложениям поставщиков из заявок '
BEGIN

insert into ri_estprices (refitmid, begdate, enddate, price, suporgid, supwrkdays, sup_notes, src_sysobjid, src_objid
, active, created_at, created_by, updated_at, updated_by)

select eri.refitmid, inv.docdate as begdate, ifnull(inv.enddate, date_add(inv.docdate, interval 5 day)) as enddate
, ofr.ord_price as price, inv.orgid as suporgid
, null as supwrkdays,  null as sup_notes
, 874 as src_sysobjid, ofr.id as src_objid
, 1 as active, ofr.created_at, ofr.created_by, ofr.updated_at,ofr.updated_by
 from eritm_offers as ofr
 join equiprqst_items as eri on eri.id=ofr.eritmid
 join equiprqsts as er on er.id=eri.rqstid and er.stageid in(9,11)
 join refitems as ri on ri.id=eri.refitmid
 join invoices as inv on inv.id=ofr.invoiceid
 join orgs as o on o.id=inv.orgid
 where eri.refitmid is not null 
 and eri.unittypeid=ri.unittypeid
 and not exists (select 1 from ri_estprices as est where est.src_sysobjid=874 and est.src_objid=ofr.id)
 order by eri.refitmid, inv.docdate;

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
        DB::unprepared('DROP PROCEDURE IF EXISTS add_ri_est_prices_from_offers');
    }
}
