<?php

namespace App\Http\Controllers;

use App\itmtype;
use App\mol_stock;
use App\objlog;
use App\org;
use App\orgstaff;
use App\report;
use App\Traits\SearchDataTrait;
use App\usrsysright;
use App\wrh;
use App\wrh_box;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MolStockController extends Controller
{
    use SearchDataTrait;

    public function __construct()
    {
        $this->middleware('auth');
        // маскируемся под wrh_stocks
        $this->sysobjid = 206;  //wrh_stocks
        $this->sysobjcode = 'wrh_stocks';
        $this->objcode = $this->sysobjcode;
    }

    protected function setInterfaceRight($docid)
    {
        $userid = \Auth::user()->id;

        //по acl указанного объекта
        $acl_sysobjcode = sysobj::where('code', $this->sysobjcode)
                ->select(db::raw("ifnull(acl_sysobjcode, code) as acl_sysobjcode"))
                ->first()->acl_sysobjcode ?? $this->sysobjcode;


        $usrrights = array();
        $usrrights['read'] = true;
        $usrrights['save'] = false;
        $usrrights['delete'] = false;

//        dd($usrrights);
        return $usrrights;
    }


    public function rep34(Request $request)
    {//Товарный запас у МОЛ

        $userid = \Auth::user()->id;

        $report_id = 34;

        $report = report::find($report_id);

        //у пользователя должно быть право на просмотр счетов/УПД
        //if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
        if (1 == 0)
            return redirect(route('home'))
                ->with(['error' => "{$report->name}: У Вас нет прав на доступ к данной информации!"]);


        $usrrights = [];
        //$usrrights['set_paytype'] = usrsysright::isUserHasRightByCode_cached($userid, 'mchnrqsts.set_paytype');

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_mol_staffid' => ''
            , 's_itmtypeid' => ''
            , 's_itmname' => ''
            , 's_ownorgid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "ws.qty<>0";
        $conditions = '';   //читаемая строка условий
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_mol_staffid') {
                    $sc = $sc . " and ws.mol_staffid = {$val}";
                    $conditions .= 'Склад: ' . orgstaff::find($val)->name ?? '?';

                } elseif ($item == 's_itmtypeid') {
                    $sc = $sc . " and ri.itmtypeid = {$val}";

                } elseif ($item == 's_itmname') {
                    $sc = $sc . " and ri.name like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_ownorgid') {
                    $sc = $sc . " and ws.ownorgid={$val}";

                }
            }
        }
        //-------------------------------------------------------------------------------------------------------------


        // --------------------------------------------------------------------

        if (1 == 1) {

            if (1 == 0) {
                $recs = mol_stock::from('mol_stocks as ws')
                    ->join('orstaffs as w', 'w.id', 'ws.staffid') //МОЛ
                    ->join('orgs as oo', 'oo.id', 'ws.ownorgid') //Владелец товаров
                    ->join('refitems as ri', 'ri.id', 'ws.refitmid') //товар/материал
                    ->leftjoin('unittypes as ut', 'ut.id', 'ri.unittypeid') //ЕИ
                    ->leftjoin('itmtypes as it', 'it.id', 'ri.itmtypeid') //категория товара/материала
//                ->leftjoin('ri_sup_prices as rp', function ($join) {
//                    $join->on('rp.refitmid', '=', 'ws.refitmid')
//                        ->whereRaw("rp.orgid = ws.ownorgid")
//                        ->whereRaw("curdate() between rp.begdate and if(rp.enddate is null,  curdate(), rp.enddate)")
//                    ;
//                })

                    ->whereRaw($sc)
                    //->wherebetween('mr.fctbegdt', [$s_begdate . ' 00:00:00', $s_enddate . ' 23:59:59'])
                    ->select('ws.*', 'ri.name as ri_name', 'w.name as wrh_name'
                        , 'ut.name as unittype_name', 'ut.decimal_dgts'
                        , 'ri.itmtypeid', 'it.name as itmtype_name', 'it.ordr as itmtype_ordr', 'ri.grossweight'
                        , 'ws.ownorgid', 'oo.name as ownorg_name')
                    ->orderby('w.name')
                    ->orderby('w.id')
                    ->orderby('oo.name')
                    ->orderby('ws.ownorgid')
                    ->orderby('itmtype_ordr')
                    ->orderby('itmtype_name')
                    ->orderby('ri.itmtypeid')
                    ->orderby('ri.name')
                    ->get();
                //dd($recs);
            } else {
                //2024-09-22 Вариант с текущей ценой владельца товара - сырой вариант!
                $sql = "select `ws`.*, `ri`.`name` as `ri_name`, `w`.`name` as `mol_name`
, `ut`.`name` as `unittype_name`
, `ut`.`decimal_dgts`, `ri`.`itmtypeid`, `it`.`name` as `itmtype_name`, `it`.`ordr` as `itmtype_ordr`, `ri`.`grossweight`
, rp.price
, `ws`.`ownorgid`, `oo`.`name` as `ownorg_name`
from `mol_stocks` as `ws`
inner join `orgstaff` as `w` on `w`.`id` = `ws`.`staffid`
inner join `orgs` as `oo` on `oo`.`id` = `ws`.`ownorgid`
inner join `refitems` as `ri` on `ri`.`id` = `ws`.`refitmid`
left join `unittypes` as `ut` on `ut`.`id` = `ri`.`unittypeid`
left join `itmtypes` as `it` on `it`.`id` = `ri`.`itmtypeid`
left join (SELECT refitmid, orgid, max(price) as price
	FROM `ri_sup_prices` sp
	WHERE curdate() between sp.begdate and if(sp.enddate is null,  curdate(), sp.enddate)
	group by refitmid, orgid) rp
	on rp.orgid=ws.ownorgid and rp.refitmid=ws.refitmid
where "
                    . $sc
                    . " order by `w`.`name` asc, `w`.`id` asc
, `oo`.`name` asc, `ws`.`ownorgid` asc
, `itmtype_ordr` asc, `itmtype_name` asc, `ri`.`itmtypeid` asc
, `ri`.`name` asc";

                $recs = DB::select(DB::raw($sql));
                //dd($recs);
            }

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid);
            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен: ' . $conditions);
        }

        $data = new \stdClass();
        $data->report = $report;

        $data->conditions = $conditions;

        //Склады
        $data->mols = orgstaff::lstFor(['with_stocks' => 1]);

        //Владельцы
        $data->ownorgs = org::lstFor(['in_mol_stocks' => 1]);

        //Категории товаров
        $data->itmtypes = itmtype::lstFor(['in_mol_stocks' => 1]);


        return view('reports.rep' . $report_id, compact('recs', 'data', 'search_params', 'usrrights'));
    }
}
