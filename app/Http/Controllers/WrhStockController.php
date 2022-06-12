<?php

namespace App\Http\Controllers;

use App\buildobj;
use App\objlog;
use App\org;
use App\report;
use App\Traits\SearchDataTrait;
use App\usrsysright;
use App\wrh_box;
use App\wrh_stock;
use App\wrh;
use App\wrhdoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WrhStockController extends Controller
{
    use SearchDataTrait;

    public function __construct()
    {
        $this->middleware('auth');
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


    public function rep33(Request $request)
    {//Товарный запас на складах


        $userid = \Auth::user()->id;

        $report_id = 33;

        $report = report::find($report_id);

        //у пользователя должно быть право на просмотр счетов/УПД
        //if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
        if (1 == 0)
            return redirect(route('home'))
                ->with(['error' => "{$report->name}: У Вас нет прав на доступ к данной информации!"]);


        $usrrights = [];
        $usrrights['set_paytype'] = usrsysright::isUserHasRightByCode_cached($userid, 'mchnrqsts.set_paytype');

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_wrhid' => ''
            , 's_itmname' => ''
            , 's_ownorgid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "ws.qty<>0";
        $conditions = '';   //читаемая строка условий
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_wrhid') {
                    $sc = $sc . " and ws.wrhid = {$val}";
                    $conditions .= 'Склад: ' . wrh::find($val)->name ?? '?';

                } elseif ($item == 's_boxid') {
                    $sc = $sc . " and ws.boxid = {$val}";
                    $conditions .= 'Отделение: ' . wrh_box::find($val)->name ?? '?';

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

            $recs = wrh_stock::from('wrh_stocks as ws')
                ->join('wrhs as w', 'w.id', 'ws.wrhid') //склад
                ->join('wrh_boxes as wb', 'wb.id', 'ws.boxid') //отделение склада
                ->join('orgs as oo', 'oo.id', 'ws.ownorgid') //Владелец товаров
                ->join('refitems as ri', 'ri.id', 'ws.refitmid') //товар/материал
                ->leftjoin('unittypes as ut', 'ut.id', 'ri.unittypeid') //ЕИ
                ->leftjoin('itmtypes as it', 'it.id', 'ri.itmtypeid') //категория товара/материала
                ->whereRaw($sc)
                //->wherebetween('mr.fctbegdt', [$s_begdate . ' 00:00:00', $s_enddate . ' 23:59:59'])
                ->select('ws.*', 'ri.name as ri_name', 'w.name as wrh_name', 'wb.name as box_name'
                    , 'ut.name as unittype_name', 'ut.decimal_dgts'
                    , 'ri.itmtypeid', 'it.name as itmtype_name', 'it.ordr as itmtype_ordr', 'ri.grossweight'
                    , 'ws.ownorgid', 'oo.name as ownorg_name')
                ->orderby('w.name')
                ->orderby('w.id')
                ->orderby('wb.name')
                ->orderby('wb.id')
                ->orderby('oo.name')
                ->orderby('ws.ownorgid')
                ->orderby('itmtype_ordr')
                ->orderby('itmtype_name')
                ->orderby('ri.itmtypeid')
                ->get();
            //dd($recs);

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid);
            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен: ' . $conditions);
        }

        $data = new \stdClass();
        $data->report = $report;

        $data->conditions = $conditions;

        //Склады
        $data->wrhs = wrh::lstFor(['with_stocks' => 1]);

        //Владельцы
        $data->ownorgs = org::lstFor(['with_stocks' => 1]);


        return view('reports.rep' . $report_id, compact('recs', 'data', 'search_params', 'usrrights'));
    }
}
