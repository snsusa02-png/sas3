<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProcFillMrOpers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
        DROP PROCEDURE IF EXISTS fill_mr_opers;
        
PROCEDURE `fill_mr_opers`()
BEGIN

truncate table mr_opers;
ALTER TABLE mr_opers AUTO_INCREMENT=101;

-- покупки
insert into mr_opers 
	(mr_id
	, suporgid, sup_gk, sup_placeid, sup_placename
    , orgid, org_gk, org_placeid, org_placename
    
    , refitmid, qty_unittypeid, qty_unit
    , itm_qty, itm_price, itm_sum
    
    , paytypeid, sale_dir
    , ordr
    , created_at, created_by, updated_at, updated_by)

select id as mr_id
	, suporgid, 0 as sup_gk, load_placeid as sup_placeid, load_placename as sup_placename
    , load_ownorgid as orgid, 1 as org_gk, null as org_placeid, null as org_placename
    
    , load_refitmid as refitmid, qty_unittypeid, qty_unit
    , load_qty as itm_qty, load_price as itm_price, load_qty*load_price as itm_sum
    
    , paytypeid, -1 as sale_dir
    , 1 as ordr
    , created_at, created_by, updated_at, updated_by
    
    -- , unload_refitmid, ownorg_sum, unload_ownorgid, orgid, org_name, unload_placeid, unload_placename, unload_qty, unload_price, unload_sum, disp_staffid, notes, active
from mchn_raids;

-- внутренние операции
insert into mr_opers 
	(mr_id
	, suporgid, sup_gk, sup_placeid, sup_placename
    , orgid, org_gk, org_placeid, org_placename
    
    , refitmid, qty_unittypeid, qty_unit
    , itm_qty, itm_price, itm_sum
    
    , paytypeid, sale_dir
    , ordr
    , created_at, created_by, updated_at, updated_by)

select id as mr_id
	, load_ownorgid, 1 as sup_gk, null as sup_placeid, null as sup_placename
    , unload_ownorgid as orgid, 1 as org_gk, null as org_placeid, null as org_placename
    
    , load_refitmid as refitmid, qty_unittypeid, qty_unit
    , load_qty as itm_qty, load_price as itm_price, load_qty*load_price as itm_sum
    
    , paytypeid, 0 as sale_dir
    , 2 as ordr
    , created_at, created_by, updated_at, updated_by
    
    -- , unload_refitmid, ownorg_sum, unload_ownorgid, orgid, org_name, unload_placeid, unload_placename, unload_qty, unload_price, unload_sum, disp_staffid, notes, active
from mchn_raids
where load_ownorgid<>unload_ownorgid;


-- продажа
insert into mr_opers (mr_id
	, suporgid, sup_gk, sup_placeid, sup_placename
    , orgid, org_gk, org_placeid, org_placename
    
    , refitmid, qty_unittypeid, qty_unit
    , itm_qty, itm_price, itm_sum
    
    , paytypeid, sale_dir
    , ordr
    , created_at, created_by, updated_at, updated_by)

select id as mr_id
	, unload_ownorgid as suporgid, 1 as sup_gk, null as sup_placeid, null as sup_placename
    , orgid as orgid, 0 as org_gk, unload_placeid as org_placeid, unload_placename as org_placename
    
    , load_refitmid as refitmid, qty_unittypeid, qty_unit
    , unload_qty as itm_qty, unload_price as itm_price, unload_qty*unload_price as itm_sum
    
    , paytypeid, +1 as sale_dir
    , 3 as ordr
    , created_at, created_by, updated_at, updated_by
    
from mchn_raids;

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
        DB::unprepared('DROP PROCEDURE IF EXISTS fill_mr_opers');
    }
}
