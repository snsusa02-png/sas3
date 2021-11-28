<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Support\Facades\Cache;


class prodplan_item extends Model
{
    //

    static public $prefix = 'prodplan_items';

    use DeleteTrait;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function prodplan()
    {
        return $this->hasOne(prodplan::class, 'id', 'docid')->withDefault();
    }

    //устарело. заменено на prodplan
    public function project()
    {
        return $this->hasOne(project::class, 'id', 'projid')->withDefault();
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function parent()
    {
        return $this->hasOne(self::class, 'id', 'parid')->withDefault();
    }

    public static function breadcrumbs($prodplan, $parplanid)
    {
        //dd($prodplan,$parplanid);
        $class_active = "active";
        $bc = "";
        if (!isset($parplanid)) {
            //находимся в корне дерева планов работ
            $parplanid = "0";
            $class_active = "";
            //$bc = '<a class="breadcrumb-item ' . $class_active . '" href="' . route('prodplans.edit', $prodplan->id) . '">' . $prodplan->name . ' </a >';
        } else {
            $class_active = "active";
            $parid = $parplanid;
            do {
                $parent = self::select('id', 'parid as parent_id', 'name')->find($parid);
                if (isset($parent))
                    $bc = '<a class="breadcrumb-item ' . $class_active . '" href="'
                        . route('prodplan_items.index', ['docid' => $prodplan->id, 'parid' => $parent->id ?? 0])
                        . '">'
                        . $parent->name . '</a>' . $bc;
                //dd($bc, isset($parent), $parent->name);

                if (isset($parent->parent_id))
                    $parid = $parent->parent_id;
                $class_active = "";
            } while (isset($parent->parent_id));

        }
        $bc = '<a class="breadcrumb-item" href="' . route('prodplans.edit', $prodplan->id)
            . '">' . $prodplan->docnum . ' (' . $prodplan->name . ')' . ' </a > '
            . '<a class="breadcrumb-item $class_active" href="'
            . route('prodplan_items.index', ['docid' => $prodplan->id, 'parid' => 0])
            . '">' . '<i class="fa fa-home" aria-hidden="true"></i> '
            . ' </a > '
            . $bc;
        // -------------------------------------------------------------------------
        return $bc;
    }


    public static function upd_norm_est($id)
    {
        if (isset($id)) {
            DB::unprepared('CALL upd_prodplan_norm_est(' . $id . ')');
            objlog::log_info(736, $id, "Произведен расчет оценки завершения по нормативным данным", 5);
            return 1;
        } else
            return 0;
    }

    public static function upd_all_norm_est()
    {
        if (1 == 1) {
            DB::unprepared('CALL upd_prodplans_norm_est()');
            objlog::log_info(736, 0, "Произведен общий расчет оценки завершения по нормативным данным", 5);
            return 1;
        } else
            return 0;
    }

    public static function upd_fact_est($id)
    {
        if (isset($id)) {
            DB::unprepared('CALL upd_prodplan_fact_est(' . $id . ')');
            objlog::log_info(736, $id, "Произведен расчет оценки завершения по фактическим данным", 5);
            return 1;
        } else
            return 0;
    }

    public static function upd_all_fact_est()
    {
        if (1 == 1) {
            DB::unprepared('CALL upd_prodplans_fact_est()');
            objlog::log_info(736, 0, "Произведен общий расчет оценки завершения по фактическим данным", 5);
            return 1;
        } else
            return 0;
    }

    public static function fill_prnts_estenddt()
    {
        if (1 == 1) {
            DB::unprepared('CALL fill_prnts_estenddt()');
            objlog::log_info(736, 0, "Максимальные значения оценок завершения работ распространены на группы", 5);
            return 1;
        } else
            return 0;
    }

    public static function calc_tree_donepcnt($id)
    {
        if (1 == 1) {
            DB::unprepared('CALL calc_tree_donepcnt(' . $id . ')');
            objlog::log_info(736, 0, "Пересчет % исполнения и распространение к родителям", 5);
            return 1;
        } else
            return 0;
    }

    public static function calc_alltree_donepcnt()
    {
        if (1 == 1) {
            DB::unprepared('CALL calc_alltree_donepcnt()');
            objlog::log_info(736, 0, "Пересчет % исполнения всех записей", 5);
            return 1;
        } else
            return 0;
    }


    public static function informer_soon_items()
    {
        $userid = \Auth::user()->id;

        //Cache::forget('informer_soon_ppr_items' . $userid);
        return Cache::remember('informer_soon_ppr_items' . $userid, now()->addMinutes(55)
            , function () use ($userid) {

                $sc = "bo.active=1";//для активных объектов

                //отбираем только те работы по которым есть факт за последние 10 дней
                //$sc .= " and exists(select 1 from cwp_facts as f where f.workid=w.id and DATEDIFF( now(), f.updated_at)<10)";

                //если у пользователя нет право просмотра справочника строительных объектов, то он должен быть в списке отв. сотрудников
                //todo:: дополнительно - сделать справочник informers и регулировать доступ через него
                if (!usrsysright::isUserHasRightByCode_cached($userid, 'buildobjs.read'))
                    $sc .= " and exists(select 1 from buildobj_staffs as bos join orgstaff as os on os.id=bos.staffid where bos.buildobjid=bo.id and os.userid={$userid})";

                //начинающиеся в ближайшие 15 дней
                $sc .= " and ( datediff(ppi.drctbegdt, curdate()) between 0 and 7"
                    . " or curdate() between ppi.fctbegdt and ifnull(ppi.fctenddt,now()) )";

                $itms = prodplan_item::from('prodplan_items as ppi')
                    ->join('prodplans as pp', 'pp.id', 'ppi.docid')
                    ->join('buildobjs as bo', 'bo.id', 'pp.buildobjid')
                    ->leftjoin('buildopertypes as bot', 'bot.id', 'ppi.buildopertypeid')
                    //->leftjoin('contracts as c', 'c.id', 'ppi.contractid')
                    //->leftjoin('orgs as co', 'co.id', 'c.orgid')
                    ->whereRaw($sc)
                    ->where('ppi.lvltypeid', 2)
                    ->select('pp.buildobjid', 'bo.name as buildobj_name'
                        , 'ppi.buildopertypeid', 'bot.name as bot_name'
                        , db::raw("datediff(ppi.drctbegdt, curdate()) as days_before")
                        , db::raw("datediff(ppi.drctenddt, ppi.drctbegdt) as workdays")
                        , 'ppi.id', 'ppi.drctbegdt', 'ppi.drctenddt', 'ppi.name', 'ppi.plnqty'
                        , db::raw("(select sum(f.qty) from prodplan_facts as f where f.planitmid=ppi.id) as done_qty")
                    )
                    ->orderby('bo.name')
                    ->orderby('ppi.drctbegdt')
                    ->get();
                //dd($itms);
                foreach ($itms as $itm) {
                    $itm->done_pcnt = ($itm->plnqty > 0) ? round($itm->done_qty / $itm->plnqty, 1) : 0;
                }

                return $itms;
            }
        );

    }

}

