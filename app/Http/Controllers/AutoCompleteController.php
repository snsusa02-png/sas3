<?php

namespace App\Http\Controllers;

use App\org;
use App\Traits\StringUtil;
use App\User;
use Illuminate\Http\Request;
use App\refitem;
use App\orgstaff;
use App\contract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoCompleteController extends Controller
{
    public function __construct()
    {
        //$this->middleware('auth');
    }

    public function refitemsAutocompleteSearch(Request $request)
    {
        $refitems = "";

        try {
            $orgid = Auth::user()->curorgid;
            //dd($orgid);

            $refitems = refitem::rqListrefitem4Auto($orgid, $request)->get();
            //dd($refitems);
        } catch (\Exception $e) {
            Log::debug($e->getMessage());
        }
        return response()->json($refitems);
    }

    public function OrgsAutocompleteSearch(Request $request)
    {
        $orgs = "";

        try {
//            $orgid=orgstaff::userOrgID();

            $orgs = Org::rqListOrg4Auto($request)->get();

        } catch (\Exception $e) {
        }
        return response()->json($orgs);
    }

    public function ContractsAutocompleteSearch(Request $request)
    {
        $list = "";

        try {

            $search_str = $request->q;
            $ownorgid = $request->ooid;

            $search_str = strtolower(preg_replace('[!|-|/| +]', ' ', $search_str));
            $search_str = preg_replace('| +|', ' ', $search_str);
            $find = explode(" ", $search_str);

            $search = " 1=1";
            $search .= " and c.ownorgid={$ownorgid}";

            if (count($find) > 0) {
                $search .= ' and ((1=1';
                foreach ($find as $f) {
                    //$search .= " and LCASE( CONCAT(' ',c.docnum,' ', c.name, ' ', c.descript, ' ', o.name))";
                    $search .= " and LCASE( CONCAT(' ', c.docnum, ' ', o.name))";
                    $search .= " like '%" . $f . "%'";
                }
                $search .= ')';
                $search .= ')';
            }

            $list = contract::from('contracts as c')
                ->select("c.id", DB::raw("concat(c.docnum,' ', c.docdate, ' ', c.name, ' ', o.name) as name")
                    , 'c.ownorgid', 'oo.name as ownorgname'
                    , 'c.orgid', 'o.name as orgname'
                )
                ->join('orgs as oo', 'oo.id', 'c.ownorgid')
                ->join('orgs as o', 'o.id', 'c.orgid')
                //->where('c.active', 1)
                ->whereRaw($search)
                ->orderby('name')
                ->orderby('c.docdate', 'desc')
                //->toSql();
                ->get();

        } catch (\Exception $e) {
            Log::debug($e->getMessage());

        }
        return response()->json($list);
    }

    public function OrgstaffAutocompleteSearch(Request $request)
    {
        $list = "";

        try {

            $search_str = $request->get("q");
            $flagtypeid = $request->get("flagid");
            $aux_prm = $request->get("aux");

            $search_str = strtolower(preg_replace('[!|-|/| +]', ' ', $search_str));
            $search_str = preg_replace('| +|', ' ', $search_str);
            $find = explode(" ", $search_str);

            $search = " 1=1";
            if (count($find) > 0) {

                $search .= ' and ((1=1';
                foreach ($find as $f) {
                    $search .= " and LCASE( CONCAT(' ', os.lname, ' ', ifnull(os.fname,' '), ' ', ifnull(os.fname,' ')))";
                    $search .= " like '% " . $f . "%'";
                }
                $search .= ')';
                $search .= ')';


//                $val = $search_str;
//                $search_flds = "os.name";
//
//                $sc = "";
//                $words = explode(" ", $val);
//                if (count($words) > 0) {
//                    $sc .= ' and (';
//
//                    //ищем "как ввел"
//                    $sc .= ' (1=1';
//                    foreach ($words as $word) {
//                        $sc .= " and {$search_flds} like '%" . $word . "%'";
//                    }
//                    $sc .= ')';
//
//                    //попробуем вариант с перекодировкой - если пользователь забыл переключить клавиатуру на русский язык
//                    $words = explode(" ", StringUtil::switcher_ru($val));
//                    $sc .= ' or (1=1';
//                    foreach ($words as $word) {
//                        $sc .= " and {$search_flds} like '%" . $word . "%'";
//                    }
//                    $sc .= ')';
//
//                    $sc .= ')';
//                }
//                $search .= $search . $sc;
                //dd($search);
            }

            //ограничение по наличию флага с указанным ID
            if (isset($flagtypeid)) {
                $search .= " and exists( select 1 from objflags ojf where ojf.sysobjid=121
                    and ojf.objid=os.id and ojf.flagtypeid={$flagtypeid})";
            }

            //2024-09-07 ограничение по доп параметрам
            if (isset($aux_prm)) {
                if ($aux_prm == 'hrs_salary'){
                    // у сотрудника должна быть определена схема расчета ЗП от часов
                    $search .= " and exists( select 1 from stf_payrolltypes as spt
	                        join salary_rate_sets srs on srs.payrolltypeid=spt.payrolltypeid
	                        join srs_hr_items hri on hri.srs_id=srs.id
                            where spt.staffid=os.id)";
                }
            }

            $list = orgstaff::from('orgstaff as os')
                ->join('orgs as o', 'o.id', 'os.orgid')
                ->select("os.id", DB::raw("concat(os.lname,' ', ifnull(os.fname,' '), ' ', ifnull(os.mname,' ')) as name")
                    , 'os.orgid', 'o.name as orgname'
                    , 'os.postname'
                    //, DB::raw("(SELECT opertypeid FROM driver_works dw where dw.staffid=os.id and dw.opertypeid is not null order by wrkdate desc limit 1) as opertypeid")
                )
                ->where('os.active', 1)
                ->whereRaw($search)
                ->orderby('os.lname')
                ->orderby('os.fname')
                ->get();

        } catch (\Exception $e) {
        }
        return response()->json($list);
    }

    public function UsersAutocompleteSearch(Request $request)
    {
        $list = "";

        try {

            $search_str = $request->q;
            $flagtypeid = $request->get("flagid");

            $sc = "1=1";
            $words = explode(" ", $search_str);

            if (count($words) > 0) {
                $srch_flds = "lcase(concat(u.lname,' ',ifnull(u.fname,' '),' ',ifnull(u.mname,' ')))";
                $sc .= ' and (';

                //ищем "как ввел пользователь"
                $sc .= ' (1=1';
                foreach ($words as $word) {
                    $sc .= " and {$srch_flds} like '%{$word}%'";
                }
                $sc .= ')';


                $enc_search_str = StringUtil::switcher_ru($search_str);
                //еще попробуем вариант с перекодировкой - если пользователь забыл переключить клавиатуру на русский язык
                if ($enc_search_str != $search_str) {
                    $words = explode(" ", $enc_search_str);
                    $sc .= ' or (1=1';
                    foreach ($words as $word) {
                        $sc .= " and {$srch_flds} like '%{$word}%'";
                    }
                    $sc .= ')';
                }
                $sc .= ')';
            }
            //dd($sc);

            //ограничение по наличию флага с указанным ID
            if (isset($flagtypeid)) {
                $sc .= " and exists( select 1 from objflags ojf where ojf.sysobjid=3
                    and ojf.objid=u.id and ojf.flagtypeid={$flagtypeid})";
            }

            $list = User::from('users as u')
                ->select("u.id", DB::raw("concat(u.lname,' ', ifnull(u.fname,' '), ' ', ifnull(u.mname,' ')) as name")
                )
                ->where('u.active', 1)
                ->whereRaw($sc)
                ->orderby('u.lname')
                ->orderby('u.fname')
                ->get();

        } catch (\Exception $e) {
        }
        return response()->json($list);
    }


    public function refitemsAC_wrhdoclst(Request $request)
    {
        $refitems = "";

        try {
            $orgid = Auth::user()->curorgid;
            //dd($orgid, $request);

            $refitems = refitem::rqListrefitem4Auto($orgid, $request)->get();
            //dd($refitems);
        } catch (\Exception $e) {
            Log::debug($e->getMessage());
        }
        return response()->json($refitems);
    }

}
