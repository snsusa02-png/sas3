<?php

namespace App;

use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\DeleteTrait;
use Illuminate\Support\Facades\Cache;
use DB;
use DateTime;
use Illuminate\Support\Facades\Log;

class buildobj extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'buildobjs';
    static public $sysobjid = 466;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function project()
    {
        return $this->hasOne(project::class, 'id', 'projectid')
            ->withDefault();
    }

    public function ownorg()
    {//Генподрядчик?
        return $this->hasOne(org::class, 'id', 'ownorgid')
            ->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function dev_org()
    {//Застройщик/Заказчик
        return $this->hasOne(org::class, 'id', 'dev_orgid')
            ->withDefault();
    }

    public function gen_org()
    {//Генподрядчик
        return $this->hasOne(org::class, 'id', 'gen_orgid')
            ->withDefault();
    }

    public function proj_org()
    {//Проектировщик
        return $this->hasOne(org::class, 'id', 'proj_orgid')
            ->withDefault();
    }

    public function contract()
    {//Договор Ген-подряда между Застройщиком и Ген-поддрядчиком
        return $this->hasOne(contract::class, 'id', 'contractid')
            ->withDefault();
    }

    public function cc_org()
    {//Строительный контроль
        return $this->hasOne(org::class, 'id', 'cc_orgid')
            ->withDefault();
    }


    public function viewpoints()
    {//Точки обзора объекта
        return $this->hasMany(viewpoint::class, 'buildobjid', 'id');
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }

    public function events()
    {
        return $this->hasMany(event::class, 'buildobjid', 'id')
            ->where('public_lvl', 2)
            ->orderby('begdt', 'desc');
    }

    static public function lstActive()
    {
        //Cache::forget(self::$prefix . '_lstTypes_' . $mchntypeid);
        $data = Cache::remember(self::$prefix . '_lstActive_' . 0, now()->addMinutes(15)
            , function () /*use ($mchntypeid)*/ {
                $lst = self::select('id', 'name')
                    ->where('active', 1);
                //if (isset($mchntypeid))
                //    $lst = $lst->where('mchntypeid', $mchntypeid);

                $lst = $lst->orderby('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function lstActiveWithBudget()
    {
        //Cache::forget(self::$prefix . '_lstActiveWithBudget_' . 0);
        $data = Cache::remember(self::$prefix . '_lstActiveWithBudget_' . 0, now()->addMinutes(15)
            , function () {
                $lst = self::from("buildobjs as bo")
                    ->select('bo.id', 'bo.name')
                    ->where('active', 1)
                    ->whereRaw("exists (select 1 from budgets as b where b.buildobjid=bo.id)");
                $lst = $lst->orderby('bo.name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }


    static public function lstForProject($projectid)
    {
        if (isset($projectid)) {
            $lst = self::from('buildobjs as bo')
                ->leftJoin('orgs as o', function ($j) { //заказчик
                    $j->on('o.id', 'bo.orgid');
                })
                ->leftJoin('orgs as o2', function ($j) {    //подрядчик??
                    $j->on('o2.id', 'bo.ownorgid');
                })
                ->where('projectid', $projectid)
                ->select('bo.*'
                    , 'o.name as orgname'
                    , 'o2.name as ownorgname'
                )
                ->orderBy('bo.name')
                ->get();

            return $lst;
        } else
            return null;
    }

    static public function lstWithUPD()
    {
        //Объекты из заявок по которым есть УПД
        Cache::forget(self::$prefix . '_lstWithUPD');
        $data = Cache::remember(self::$prefix . '_lstWithUPD', now()->addMinutes(15)
            , function () {
                $lst = self::from("buildobjs as bo")
                    ->select('bo.id', 'bo.name')
                    ->where('active', 1)
                    ->whereRaw("exists (select 1 from equiprqsts as er
                        join equiprqst_items as eri on eri.rqstid=er.id
                        join eritm_offers as ofr on ofr.eritmid=eri.id
                        join eritm_supplies as sup on sup.offerid=ofr.id
                            and sup.invoiceid is not null
                        where er.buildobjid=bo.id)");
                $lst = $lst->orderby('bo.name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function list_for_budget_owner($orgid)
    {
        //массив строительных объектов, имеющих бюджеты с ЦФО = $orgid

        $list = [];
        if (isset($orgid)) {
            $list = buildobj::from('buildobjs as bo')
                ->whereRaw("bo.id in (
                            select distinct buildobjid
                            FROM budgets as b
                            where b.orgid= {$orgid})"
                )
                ->select('id', 'name')
                //->orderby('ordr')
                ->orderby('name')
                ->get()->pluck('name', 'id')->toArray();
        }
        return $list;
    }


    static public function search_cond($params)
    {

        $sc = "1=1";

        //пользователь ДОЛЖЕН иметь доступ к категории информации, для того, чтобы работать с ней
        $userid = \Auth::user()->id;
//        if (!usrsysright::isUserHasRightByCode_cached($userid, 'acs.admin'))
//            $sc .= " and exists (select 1 from user_acs as uac where uac.acsid=ac.id and uac.userid={$userid})";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'id') {

                        $sc .= " and bo.id={$val}";

                    } elseif ($key == 'active') {
                        //объект строительства активен
                        $sc .= " and bo.active";

                    } elseif ($key == 'active_or_current') {
                        //объект строительства активен, или соответствует $val
                        $sc .= " and (bo.active or bo.id={$val})";

                    } elseif ($key == 'has_budget') {
                        //для объекта строительства определены бюджеты
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists(select 1 from budgets as b where b.buildobjid = bo.id)";

                    } elseif ($key == 'budget_orgid') {
                        //объект строительства имеет бюджет с владельцем orgid=$val
                        $sc .= " and exists(select 1 from budgets as b where b.buildobjid = bo.id and b.orgid={$val})";

                    } elseif ($key == 'in_equiprsts') {
                        //объект строительства присутствует в заявках на материалы
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists(select 1 from equiprqsts as er where er.buildobjid = bo.id)";

                    } elseif ($key == 'in_jts') {
                        //объект строительства присутствует в Документах учета рабочего времени
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists(select 1 from jobtimesheets as jts where jts.buildobjid = bo.id)";

                    } elseif ($key == 'in_invoices') {
                        //объект строительства присутствует в Счетах
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists(select 1 from invoices as inv where inv.buildobjid = bo.id)";

                    } elseif ($key == 'in_wrkreps') {
                        //объект строительства присутствует в Отчетах о работе
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists(select 1 from wrkreps as wr where wr.buildobjid = bo.id)";

                    } elseif ($key == 'in_estdocs') {
                        //объект строительства присутствует в Сметах
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists(select 1 from estdocs as ed where ed.buildobjid = bo.id)";

                    } elseif ($key == 'in_documents') {
                        //объект строительства связан с документами Архива документов
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists(select 1 from obj_links as lnk
                             where lnk.lnksysobjid=466 and lnk.lnkobjid=bo.id and lnk.sysobjid=1701)";

                    } elseif ($key == 'in_buildobj_staff') {
                        //переданный id пользователя есть в списке ответственных сотрудников объекта
                        if (!usrsysright::isUserHasRightByCode_cached($val, 'buildobjs.read')) {
                            $sc .= " and exists( select 1 from buildobj_staffs as bos
                                join orgstaff os on os.id=bos.staffid and os.userid={$val} and os.active=1
                                where bos.buildobjid=bo.id and bos.active=1)";
                        }

                    } elseif ($key == 'in_prodplans') {
                        //объект строительства есть в ППР
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists( select 1 from prodplans as pp where pp.buildobjid=bo.id)";

                    } elseif ($key == 'in_aosrs') {
                        //объект строительства есть в Актах скрытых работ
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists( select 1 from aosrs as asr where asr.buildobjid=bo.id)";

                    } elseif ($key == 'user_in_stafflist') {
                        //отбираем все объекты строительства где пользователь включен в список сотрудников
                        // - без учета права чтения на Объекты
                        $sc .= " and exists (select 1 from buildobj_staffs as bos join orgstaff as os on os.id=bos.staffid and os.userid={$val} where bos.buildobjid=bo.id)";

                    } elseif ($key == 'not_dlvrd_rqsts') {
                        //отбираем все объекты строительства где есть недополученные заказы
                        $sc .= " and exists (select 1 from equiprqsts as er where er.buildobjid=bo.id and
			                        exists( select 1 from equiprqst_items as eri where eri.rqstid=er.id and ord_qty>dlvrd_qty) )";

                    } elseif ($key == 'link_wrh') {
                        //отбираем все объекты строительства, обслуживаемые указанным складом
                        $sc .= " and exists (select 1 from buildobj_wrhs as bow where bow.buildobjid=bo.id and bow.wrhid={$val} )";

                    } elseif ($key == 'upd_ownorgid') {

                        $sc .= " and exists (select 1 from invoices as inv
                             join eritm_supplies as sup on sup.invoiceid=inv.id
                             join equiprqst_items as eri on eri.id=sup.eritmid
                             join equiprqsts as er on er.id=eri.rqstid
                                where inv.doctypeid=2 and er.buildobjid=bo.id and inv.ownorgid={$val}";

                        if (isset($params['upd_begdate']) and array_search('upd_begdate', $used_params) == 0) {
                            $upd_begdate = $params['upd_begdate'];
                            $sc .= " and inv.docdate>='{$upd_begdate}'";
                            $used_params[] = 'upd_begdate';
                        }
                        if (isset($params['upd_enddate']) and array_search('upd_enddate', $used_params) == 0) {
                            $sc .= " and inv.docdate<='" . $params['upd_enddate'] . "'";
                            $used_params[] = 'upd_enddate';
                        }

                        $sc .= ")";
                    } elseif ($key == 'upd_begdate') {
                        //
                        $sc .= " and exists (select 1 from invoices as inv
                             join eritm_supplies as sup on sup.invoiceid=inv.id
                             join equiprqst_items as eri on eri.id=sup.eritmid
                             join equiprqsts as er on er.id=eri.rqstid
                                where inv.doctypeid=2 and er.buildobjid=bo.id
                                    and inv.docdate>='" . $params['upd_begdate'] . "'";

                        if (isset($params['upd_enddate']) and array_search('upd_enddate', $used_params) == 0) {
                            $sc .= " and inv.docdate<='" . $params['upd_enddate'] . "'";
                            $used_params[] = 'upd_enddate';
                        }
                        $sc .= ")";

                    } elseif ($key == 'upd_enddate') {
                        $sc .= " and exists (select 1 from invoices as inv
                             join eritm_supplies as sup on sup.invoiceid=inv.id
                             join equiprqst_items as eri on eri.id=sup.eritmid
                             join equiprqsts as er on er.id=eri.rqstid
                                where inv.doctypeid=2 and er.buildobjid=bo.id
                                    and inv.docdate<='" . $params['upd_enddate'] . "'";

                        if (isset($params['upd_begdate']) and array_search('upd_begdate', $used_params) == 0) {
                            $sc .= " and inv.docdate>='" . $params['upd_begdate'] . "'";
                            $used_params[] = 'upd_begdate';
                        }
                        $sc .= ")";
                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }


    static public function lstFor($params)
    {
        //2021-02-18 SNS. универсальный конструктор массива с id, name строительных объектов
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"


        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);
            //Log::info($sc);

            $lst = buildobj::from('buildobjs as bo')
                ->whereRaw($sc)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()->pluck('name', 'id')->toArray();
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2022-01-18 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget('lstFor_' . $hash);
            return Cache::remember('lstFor_' . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
        } else
            return null;
    }

}
