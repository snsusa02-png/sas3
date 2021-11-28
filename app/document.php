<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\Excludable;
use App\Traits\FilesTrait;
use App\Traits\StaffsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class document extends Model
{
    use DeleteTrait;
    use FilesTrait;
    use StaffsTrait;
    use Excludable;

    protected $guarded = [];

    static public $prefix = 'documents';
    static public $sysobjid = 1701;

    protected $hidden = ['id', 'created_by', 'updated_by'];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function doctype()
    {
        return $this->hasOne(doctype::class, 'id', 'doctypeid')
            ->withDefault();
    }

    public function ownorg()
    {//Организация-владелец записи в Архиве документов
        return $this->hasOne(org::class, 'id', 'ownorgid')->withDefault();
    }

    public function org()
    {//Организация-владелец записи в Архиве документов
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    public function contract()
    {//Договор между Организацией-владельцем и контрагентом
        return $this->hasOne(contract::class, 'id', 'contractid')->withDefault();
    }


    public function src_org()
    {//Организация-Источник
        return $this->hasOne(org::class, 'id', 'src_orgid')
            ->withDefault();
    }

    public function tgt_org()
    {//Организация-Получатель
        return $this->hasOne(org::class, 'id', 'tgt_orgid')
            ->withDefault();
    }

    public function ac()
    {
        return $this->hasOne(ac::class, 'id', 'acsid')->withDefault();
    }

    static public function dirtypes()
    {
        return [
            1 => 'входящий',
            2 => 'исходящий',
            3 => 'внутренний',
        ];
    }

    static public function statuses()
    {
        return [
            0 => 'черновик',
            2 => 'проект',
            4 => 'подписан (действует)',
//            6 => 'отменен',
//            8 => 'завершен (исполнен)',
//            10 => 'расторгнут',
        ];
    }

    public function linked_documents()
    {
        return $this->hasMany(obj_link::class, 'objid', 'id')
            ->where([
                'sysobjid' => self::$sysobjid,
                'lnksysobjid' => self::$sysobjid,
            ]);
    }


    public function linked_tasks()
    {
        return $this->hasMany(task::class, 'srcobjid', 'id')
            ->where([
                'srcsysobjid' => self::$sysobjid,
            ]);
    }

    public function linked_objs()
    {
        return $this->hasMany(obj_link::class, 'objid', 'id')
            ->where([
                'sysobjid' => self::$sysobjid,
            ]);
    }

//    public function staffs()
//    {
//        return $this->hasMany(obj_staff::class, 'objid', 'id')
//            ->Join('orgstaff as os', 'os.id', 'obj_staffs.staffid')
//            ->Join('orgs as o', 'o.id', 'os.orgid')
//            ->leftJoin('orgposts as op', 'op.id', 'os.postid')
//            ->leftJoin('roletypes as rt', 'rt.id', 'obj_staffs.roletypeid')
//            ->where('sysobjid', self::$sysobjid)
//            ->select('obj_staffs.*'
//                , 'o.name as org_name'
//                , db::raw("ifnull(op.name, os.postname) as post_name")
//                , 'rt.name as roletype_name')
//            ->orderby('staffname');
//    }


    public function doc_orgs()
    {
        return $this->hasMany(obj_org::class, 'objid', 'id')
            ->Join('orgs as o', 'o.id', 'obj_orgs.orgid')
            ->leftJoin('roletypes as rt', 'rt.id', 'obj_orgs.roletypeid')
            ->where('obj_orgs.sysobjid', self::$sysobjid)
            ->select('obj_orgs.*'
                , 'o.name as org_name'
                , 'rt.name as roletype_name')
            ->orderby('obj_orgs.ordr');
    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->leftJoin('roletypes as rt', 'rt.id', 'obj_readers.roletypeid')
            ->where('sysobjid', self::$sysobjid)
            ->select('obj_readers.*', 'rt.name as roletype_name')
            ->orderby('mustread', 'desc')
            ->orderby('firstread_at');
    }

    public function real_readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->where('read_cnt', '>', 0)
            ->orderby('firstread_at');
    }

    public function tags()
    {
        return $this->hasMany(objtag::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('tag');
    }

    public function comments()
    {
        return $this->hasMany(obj_comment::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }


    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = $this->doctype->name . trim(' ' . $this->name) . ' №' . $this->docnum
                . ' от ' . date_format(date_create($this->docdate), "d.m.Y");
            if (isset($this->docsum))
                $rslt .= ' сумма: ' . number_format($this->docsum, 2);
            return $rslt;
        } else
            return null;
    }

    public function getShortInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = $this->name . ' №' . $this->docnum
                . ' от ' . date_format(date_create($this->docdate), "d.m.Y");
            return $rslt;
        } else
            return null;
    }

    public static function getInfo($contractid)
    {
        if (isset($contractid)) {
            $rq = self::select("docnum", "docdate", 'name')
                ->find($contractid);
            if (isset($rq)) {
                return $rq->name . ' №' . $rq->docnum
                    . ' от ' . date_format(date_create($rq->docdate), "d.m.Y");
            } else
                return null;
        }
    }


    static public function usedTags()
    {
        $cache_key = self::$prefix . '_usedTypes';
        Cache::forget($cache_key);
        $data = Cache::remember($cache_key, now()->addMinutes(8)
            , function () {
                $lst = objtag::from('objtags as t')
                    ->select('tag as tid', 'tag as tname')
                    ->where('sysobjid', self::$sysobjid)
                    ->orderBy('tag')
                    ->get()
                    ->pluck('tname', 'tid')->toArray();

                return $lst;
            }
        );
        return $data;
    }


    static public function usedBuildObjs()
    {
        $cache_key = self::$prefix . '_' . 'usedBuildObjs';
        Cache::forget($cache_key);
        $data = Cache::remember($cache_key, now()->addMinutes(8)
            , function () {
                $lst = obj_link::from('obj_links as lnk')
                    ->join('buildobjs as bo', 'bo.id', 'lnk.lnkobjid')
                    ->where('sysobjid', self::$sysobjid)
                    ->where('lnksysobjid', 466)
                    ->select('bo.id', 'bo.name')
                    ->orderBy('bo.name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function buildopertypes($contractid)
    {
        //массив Видов работ, связанных с контрактом
        // - определяем через связь с видами работ разделов бюджета переданому в подряд по указанному контракту

        //Cache::forget('contract_buildopertypes_' . $contractid );
        return Cache::remember('contract_buildopertypes_' . $contractid
            , now()->addMinutes(15)
            , function () use ($contractid) {
                return buildopertype::from('buildopertypes as bot')
                    ->whereRaw(" bot.id in (select bi.buildopertypeid
                        from budget_items as bi
                        join budgets as b on b.id=bi.budgetid
                        and b.par_contractid={$contractid})")
                    ->select('bot.id', 'bot.name')
                    ->orderby('bot.ordr')
                    ->orderby('bot.name')
                    ->get()
                    ->pluck('name', 'id')
                    ->toArray();
            }
        );
    }


    static public function search_cond($params)
    {

        $sc = "1=1";

        //пользователь ДОЛЖЕН иметь доступ к категории информации, для того, чтобы работать с ней
        $userid = \Auth::user()->id;
        if (!usrsysright::isUserHasRightByCode_cached($userid, 'acs.admin'))
            $sc .= " and exists (select 1 from user_acs as uac where uac.acsid=d.acsid and uac.userid={$userid})";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'active') {
                        $sc .= " and d.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (d.active=1 or d.id={$val})";

                    } elseif ($key == 'new_for_user_or_current') {
                        $t_userid = $val[0] ?? 0;
                        $t_acsid = $val[1] ?? 0;
                        $sc .= " and d.active=1
                            and not exists (select 1 from user_acs as uac where uac.acsid=d.acsid
                            and uac.userid={$t_userid} and uac.acsid<>{$t_acsid})";
                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;
    }

    static public function lstFor($params)
    {
        //2021-02-18 SNS. универсальный конструктор массива с id, name документов
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('documents as d')
                ->whereRaw($sc)
                ->select('d.id', 'd.name')
                ->orderBy('d.docdate', 'asc')
                ->orderBy('d.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            //asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-10-14 SNS. кэшируемый результат списка

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


    static public function make_template($id, $ref_sysobjid = null)
    {
        $userid = \Auth::user()->id;
        $ref_sysobjid = $ref_sysobjid ?? self::$sysobjid;

        $rec = document::find($id);
        if (!isset($rec))
            return redirect(route('home'))->with(['error' => 'doc not found']);

        $document = array_filter($rec->makeHidden(['id', 'created_at', 'updated_at', 'name', 'docnum', 'docdate', 'outline'])->toArray());

        $buildobjid = obj_link::getFirstLnkId($ref_sysobjid, $rec->id, 466);
        if (isset($buildobjid))
            $document['buildobjid'] = $buildobjid;

        $buildopertypeid = obj_link::getFirstLnkId($ref_sysobjid, $rec->id, 467);
        if (isset($buildopertypeid))
            $document['buildopertypeid'] = $buildopertypeid;

        $document['tags'] = objtag::lstTags($ref_sysobjid, $id);

        $orgs = obj_org::where(['sysobjid' => $ref_sysobjid,
            'objid' => $rec->id])->get()
            ->makeHidden(['id', 'created_at', 'updated_at', 'created_by', 'updated_by'])->toArray();
        //уберем пустые элементы в каждой записи массива
        foreach ($orgs as $elm) {
            $elm = array_filter($elm);
        }

        $obj_staffs = obj_staff::where(['sysobjid' => $ref_sysobjid, 'objid' => $rec->id])->get()
            ->makeHidden(['id', 'created_at', 'updated_at', 'created_by', 'updated_by'])->toArray();
        //уберем пустые элементы в каждой записи массива
        foreach ($obj_staffs as $elm) {
            $elm = array_filter($elm);
        }

        $template_js = [
            'document' => $document,
            'obj_orgs' => $orgs,
            'obj_staffs' => $obj_staffs,
        ];
        $template_js = json_encode($template_js);

        user_template::addOrUpdate($userid, $ref_sysobjid, $template_js);

    }


    public static function informer_new_docs($userid)
    {
        //Cache::forget('informer_new_docs_' . $userid);

        return Cache::remember('informer_new_docs_' . $userid, now()->addMinutes(15)
            , function () use ($userid) {

                $sc = " d.created_at >= date_sub(curdate(), INTERVAL 7 day)";
                $sc .= "and exists(select 1 from userorgs as uo where uo.orgid=d.ownorgid and uo.userid={$userid}
                            and uo.active=1 and now() between uo.begdt and ifnull(uo.enddt,now()) )";

                $lst = self::from('documents as d')
                    ->join('doctypes as dt', 'dt.id', 'd.doctypeid')
                    ->join('orgs as oo', 'oo.id', 'd.ownorgid')
                    ->leftjoin('orgs as o', 'o.id', 'd.orgid')
                    //->where('inv.doctypeid', 2)
                    ->whereRaw($sc)
                    ->select('d.id', 'd.dirtypeid', 'd.docnum', 'd.docdate', 'd.name'
                        , 'dt.name as doctype_name'
                        , 'd.ownorgid', 'd.ownorg_regnum', 'd.ownorg_regdate', 'oo.name as ownorgname'
                        , 'd.orgid', 'd.org_regnum', 'd.org_regdate', 'o.name as orgname'
                        , 'd.docsum', 'd.created_at'
                    )
                    ->orderby('oo.name', 'desc')
                    ->orderby('d.created_at', 'desc')
                    ->orderby('d.docnum')
                    ->get();

                return $lst;
            }
        );

    }


    public static function clearCache()
    {
        //Очистка кэшированных данных, основанных на записях из Архива документов
        $users = User::getFor(['active' => 1], ['id']);
        //dd($users);
        foreach ($users as $user) {

            Cache::forget('informer_new_docs_' . $user->id);

        }
    }

    public static function informer_statistics()
    {
        //Cache::forget('informer_doc_stat');
        return Cache::remember('informer_doc_stat', now()->addMinutes(15)
            , function () {

                return [
                    'doc_cnt' => document::count()
                    , 'doc_file_cnt' => objfile::where(['sysobjid' => self::$sysobjid])->count()
                    , 'user_rqst_cnt' => obj_reader::where('sysobjid', self::$sysobjid)->whereNotIn('userid', [12, 64])->sum('read_cnt')
                ];
            }
        );
    }
}

