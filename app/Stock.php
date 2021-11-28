<?php

namespace Modules\Stock\Entities;

use App\objpref;
use App\org;
use App\orgdog;
use App\RefItem;
use App\objextid;
use DB;
use Modules;
use App\Http\Middleware\IStock;
use Modules\Stock\Entities\org_wrh;

//use Modules\ExtendCharacters\Entities\Extendcharacter;

class Stock implements IStock
{

    function __constructor()
    {
        $this->module = Module::find('Stock');
    }

    function active()
    {
        return true;
    }

    function auxinfo_org_wrhs($orgid)
    {
        $data = org_wrh::from('org_wrhs as ow')
            ->join('wrhs as w', 'w.id', '=', 'ow.wrhid')
            ->where('ow.orgid', $orgid)
            ->where('ow.active', 1)
            ->select('w.name as name')
            ->orderBy('ow.created_at', 'asc')
            ->skip(0)->take(4)
            ->get()->pluck("name");
        return $data;
    }

    function org_wrh_stock($orgid, $itmtypeid)
    {
        $ownorgid = org::OwnOrgIDByOrgID($orgid);
//        $ownorgid=null;
        if (isset($ownorgid)) {
            if ($orgid == $ownorgid) {
                //Зашли в систему от Продавца. Смотрим на все склады где есть его товары

                $data = refitem::from('refitems as ri')
                    ->join(
                        DB::raw('(SELECT refitmid, sum(qty) as qty
                  from wrh_stocks as ws
                 where ws.ownorgid = ' . $ownorgid . '
                    and qty>0
                 GROUP BY refitmid) as a'),
                        function ($join) {
                            $join->on('ri.id', '=', 'a.refitmid');
                        })
                    ->leftjoin('unittypes as ut', 'ut.id', '=', 'ri.unittypeid');

                if (isset($itmtypeid)) {
                    $data = $data->where('ri.itmtypeid', $itmtypeid);
                }
                $data = $data->select('a.refitmid', 'a.qty', 'ut.name as unittypename')
                    ->get();
            } else {
                $data = refitem::from('refitems as ri')
                    ->join(
                        DB::raw('(SELECT refitmid, sum(qty) as qty
                 from wrh_stocks as ws
                 join org_wrhs as ow 
                 on ow.wrhid=ws.wrhid
                 and ow.active=1
                 and ow.orgid = ' . $orgid . '
                  where ws.ownorgid = ' . $ownorgid . '
                    and qty>0
                 GROUP BY refitmid) as a'),
                        function ($join) {
                            $join->on('ri.id', '=', 'a.refitmid');
                        })
                    ->leftjoin('unittypes as ut', 'ut.id', '=', 'ri.unittypeid');

                if (isset($itmtypeid)) {
                    $data = $data->where('ri.itmtypeid', $itmtypeid);
                }
                $data = $data->select('a.refitmid', 'a.qty', 'ut.name as unittypename')
                    ->get();
            }
//        dd($data->toSql());


            //Форматирование результатов
            $userid = \Auth::User()->id;
            $FormatType = objpref::userPrefVal($userid, 25);
            //$FormatType = 1;

            foreach ($data as $d) {

                if ($FormatType == 1) {
                    //Show Real Qty
                    $d->show_qty = number_format($d->qty, 0) . ' ' . $d->unittypename;
                } elseif ($FormatType == 2) {
                    //No show real qty, if Qty > 10
                    if ($d->qty > 10)
                        $d->show_qty = '>10 ' . $d->unittypename;
                } else
                    //Same as 1
                    $d->show_qty = number_format($d->qty, 0) . ' ' . $d->unittypename;
            }
            return $data;
        }
        return null;
    }

    function ri_stockinfo_view()
    {
//        return "stock::blank";
        return "stock::refitems.stockinfo";
    }

    function ri_stocks_view()
    {
        return "stock::refitems.ri_stocks";
    }

    function ri_stocks($refitmid)
    {
        $ri_stocks = wrh_stock::from('wrh_stocks as ws')
            ->join('orgs as oo', 'oo.id', '=', 'ws.ownorgid')
            ->join('wrhs as w', 'w.id', '=', 'ws.wrhid')
            ->leftjoin('orders as ord', 'ord.id', '=', 'ws.ordid')
            ->select('ws.id', 'ws.ownorgid', 'oo.name as ownorgname', 'w.name as wrh_name'
                , 'ws.wrhid', 'ws.ordid', 'ord.ordnum'
                , 'ws.qty', 'ws.plnincqty', 'ws.plnoutqty'
                , 'ws.refitmid')
            ->where('refitmid', $refitmid)
            ->orderby('oo.name')
            ->orderby('w.name')
            ->orderby('ws.updated_at')
            ->get();

        return $ri_stocks;
    }

    function ri_stockdocs_view()
    {
        return "stock::refitems.ri_stockdocs";
    }

    function ri_stock_docs($refitmid)
    {
        $ri_stock_docs = wrhdoclst::from('wrhdoclst as dl')
            ->join('wrhdocs as d', 'd.id', '=', 'dl.docid')
            ->join('orgs as oo', 'oo.id', '=', 'd.ownorgid')
            ->join('wrhdoctypes as dt', 'dt.id', '=', 'd.doctypeid')
            ->leftjoin('wrhdoctypes as dlt', 'dlt.id', '=', 'dl.subtypeid')
            ->select('d.ownorgid', 'oo.name as ownorgname'
                , 'dt.name as doctypename'
                , 'dl.docid', 'd.docdate', 'd.docnum', 'd.docsigned')
            ->selectraw('d.docsigned*ifnull(dlt.forstock,dt.forstock)*dl.qty as qty
            , (1-d.docsigned)*ifnull(dlt.forstock,dt.forstock)*dl.qty as plnqty')
            ->where('dl.refitmid', $refitmid)
            ->orderby('oo.name')
            ->orderby('dl.id')
            ->get();
        return $ri_stock_docs;
    }

    function ord_wrhdocs($ordid)
    {
        // Implement ord_wrhdocs() method.
//        return wrhdoc::where('ordid', $ordid)->get();
        //другая привязка
        return wrhdoc::where([['sysobjid', 131], ['objid', $ordid]])->get();
    }

    function obj_wrhdocs($sysobjid, $objid)
    {
        // Implement ord_wrhdocs() method.
        return wrhdoc::where([['sysobjid', $sysobjid], ['objid', $objid]])->get();
    }

    function on_ordMngrAgree($ordid)
    {
        //действия, связанные с подтверждением менеджера, что заказ подготовлен
        return null;
    }

    function on_ordMngrDisagree($ordid)
    {
        //действия, связанные с возобновлением работы менеджера с заказом
        // - отказ от подготовленности
        return null;
    }

    function wrh_name($wrhid)
    {
        $data = wrh::select('name')->find($wrhid);
        return $data->name ?? '-?-';
    }

    function newItemByExtID($extsysid, $itmextid, $itmdata)
    {
        $sysobjid = 202;
        //перепроверим - вдруг уже есть такой склад:
        $itmid = objextid::objid_by_extsysid_extid($extsysid, $sysobjid, $itmextid);
        if (!isset($itmid) and isset($itmextid)) {

            try {
                DB::beginTransaction();

                $userid = $itmdata['created_by'] ?? 0;
                $itmdata['updated_by'] = $itmdata['updated_by'] ?? $userid;

                //Добавить запись о складе
                $rec = new wrh($itmdata);
                $rec->save();
                $itmid = $rec->id;

                //Добавить идентификатор товара во внешней системе $extsysid
                $ext = new objextid([
                    "extsysid" => $extsysid,
                    "sysobjid" => $sysobjid,
                    "objid" => $itmid,
                    "extid" => $itmextid,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $ext->save();

                DB::commit();

            } catch (\Exception $e) {
                //var_dump($e->getTraceAsString());
                \Log::debug($e->getMessage());
                return $e->getMessage();
            } finally {
            }
        }
        return $itmid;
    }

    //    function getData($items){
//        print "Ext.getData\n";
//        $data=extendcharacter::whereIn("itm_id",$items->pluck('id')->toArray())
//                    ->get()//->groupBy("itm_id")
//                    ;
//        //dd($data[1][0]->id);
//        return $data;
//    }

//    function HeadView() {return 'extendcharacters::head' ;}
//    function DataView() { return 'extendcharacters::data';}

}

?>
