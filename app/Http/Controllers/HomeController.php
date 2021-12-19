<?php

namespace App\Http\Controllers;

use App\advert;
use App\contract;
use App\cwp_work;
use App\equiprqst_expense;
use App\invoice;
use App\machine;
use App\meeting;
use App\news;
use App\obj_approval;
use App\obj_msg;
use App\obj_reader;
use App\objfile;
use App\opertype;
use App\org_extservice;
use App\org_saldo;
use App\orgacnt_sum;
use App\orgplnpay_item;
use App\prodplan_fact;
use App\prodplan_item;
use App\equiprqst;
use App\qcheck_item;
use App\report;
use App\usrsysright;
use App\vp_photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use MongoDB\Driver\Query;
use App\orgstaff;
use App\equiprqst_item;
use App\document;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $userid = \Auth::user()->id;

        $data = new \stdClass();

        $lstRights = [
            'mchnrqsts.approve',
            'admin-global',
            'meetings.read',
        ];
        $usrrights = array();
        foreach ($lstRights as $code) {
            $usrrights[$code] = usrsysright::isUserHasRightByCode_cached($userid, $code);
        }
        //dd($usrrights);


        //согласование остатка бюджета на материалы - устарело после ввода системы бюджетов
        $data->finconfirms = null;



        //текущий баланс организаций холдинга//------------------------------------------------
        $data->ownorg_saldos = org_saldo::informer_saldos();
        //-------------------------------------------------------------------------------------

        //детализация балансов контрагентов в разрезе организаций холдинга//-------------------
        $data->ownorg_saldo_details = org_saldo::informer_ownorg_saldo_details();
        //-------------------------------------------------------------------------------------

        //Сводка по видам деятельности//-------------------------------------------------------
        $data->opertypes_sums = opertype::informer_opertypes_sums();
        //-------------------------------------------------------------------------------------


        //непрочитанные сообщения//-------------------------------------------------------------
        //$data->newmsgs = obj_msg::unread_msgs($userid);
        //dd($data->newmsgs);
        //-------------------------------------------------------------------------------------


        //$data->adverts = advert::lstActive();
        //dd($data->adverts);

        //Популярные отчеты пользователя ------------------------------------------------------
        //$data->popular_reports = report::popular($userid);
        //-------------------------------------------------------------------------------------


        //dd($data);
        return view('home', compact('data', "usrrights"));
    }


    public function welcome()
    {
        $userid = \Auth::user()->id;

        $data = new \stdClass();

        $lstRights = [
            'mchnrqsts.approve',
            'admin-global',
            'meetings.read',
        ];
        $usrrights = array();
        foreach ($lstRights as $code) {
            $usrrights[$code] = usrsysright::isUserHasRightByCode_cached($userid, $code);
        }
        //dd($usrrights);

        //$data->adverts = advert::lstActive();
        //dd($data->adverts);


        //dd($data);

        return view('welcome', compact('data', "usrrights"));
    }


    public static function refresh()
    {

        $userid = \Auth::user()->id;

        Cache::forget('informer_orgacntsums_' . $userid);
        Cache::forget('orgextsrvcsums_for_user_' . $userid);
        Cache::forget('informer_plnpays4regpay');

        Cache::forget('last_viewpoint_photos_*');
        Cache::forget('last_viewpoint_photos_' . $userid);

        Cache::forget('news_digest');

        Cache::forget('suporg_est_diff_sums_'); //рейтинг поставщиков

        return redirect(route("home"));
    }

    public static function isotope()
    {
        $data = new \stdClass();

        return view('test.isotope', compact('data'));
    }
}
