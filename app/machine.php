<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Imports\invoiceImport;
use App\Traits\Result;
use Maatwebsite\Excel\Facades\Excel;
use Log;
use DateTime;
use App\mchncontrorg;
use Illuminate\Support\Facades\DB;

class machine extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'machines';
    static public $sysobjid = 482;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    static public $ft = [
        1 => 'ДТ', 2 => 'АИ92', 3 => 'АИ95', 4 => 'АИ98'
    ];

    static public function fueltypes()
    {
        return [
            1 => 'ДТ', 2 => 'АИ92', 3 => 'АИ95', 4 => 'АИ98'
        ];
    }

    public function mchntype()
    {
        return $this->hasOne(mchntype::class, 'id', 'mchntypeid')
            ->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function photo()
    {
        return $this->hasOne(objfile::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->where('doctypeid', 254);
    }

    public function images()
    {
        return $this->hasMany(objfile::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->where('doctypeid', 254);
        //return $this->hasMany(ri_image::class, 'refitmid', 'id');
    }

    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = $this->name . '/ №' . $this->regnum . '.  ' . $this->org->name;
            return $rslt;
        } else
            return null;
    }


    public function getRegNumNameAttribute()
    {
        if (isset($this->id)) {
            $rslt = $this->regnum . ' /' . $this->name;
            return $rslt;
        } else
            return null;
    }


    static public function AuxInfo($machine)
    {
        $info = [];
        if (isset($machine->id)) {
            $machineid = $machine->id;

            //Заявки
            if (1 == 1) {
                Cache::forget(self::$prefix . '_aux_rqsts_.' . $machineid);
                $data = Cache::remember(self::$prefix . '_aux_rqsts_.' . $machineid, now()->addMinutes(15)
                    , function () use ($machineid) {

                        $recs = mchnrqst::from('mchnrqsts as mr')
                            ->where('mr.asgnmachineid', $machineid)
                            ->where('mr.plnbegdt', '>=', now())
                            ->select('mr.name', 'mr.descript', 'mr.active', 'mr.plnbegdt', 'mr.plnenddt', 'mr.inituserid')
                            ->orderBy('mr.created_at', 'asc')
                            ->orderBy('mr.id', 'asc')
                            ->skip(0)->take(4)
                            ->get();

                        $tstr = "";
                        $i = 0;
                        foreach ($recs as $rec) {
                            $i++;
                            if ($i > 1) {
                                $tstr = $tstr . ", <br>";
                            }
                            if ($i > 3) {
                                $tstr = $tstr . " ...";
                                break;
                            }
                            //$dt = new DateTime((string)$rec->begdt);
                            $dt0 = date_create((string)$rec->begdt);
                            $dt0c = date_format($dt0, "Y-m-d H:i");
                            $dt1 = new DateTime((string)$rec->enddt);
                            $interval = date_diff($dt0, $dt1);
                            if ($interval->d == 0)
                                $dt1c = date_format($dt1, "H:i");
                            else
                                $dt1c = date_format($dt1, "Y-m-d H:i");
//                            dd($interval->d,$dt1c);
                            $tstr = $tstr . $rec->name . ' ' . $dt0c . ' - ' . $dt1c;
                        }
                        $cnt = mchnrqst::whereRaw("'" . $machineid . "' in ( rqstmachineid, asgnmachineid)")
                            ->where('plnbegdt', '>=', now())
                            ->selectraw('count(*) as cnt')
                            ->first();

                        return ['sample' => $tstr, 'reccount' => $cnt ? $cnt->cnt : 0];
                    });
                if (isset($data))
                    array_push($info,
                        ['name' => "Заявки",
                            'route' => 'machine_rqsts.index',
                            'sample' => $data['sample'],
                            'reccount' => $data['reccount'],
                            'btn-class' => 'bg-success'
                        ]
                    );
            }
            //--------------------------------------------------------------


            //Заявки
            if (1 == 0) {
                Cache::forget(self::$prefix . '_aux_prices_.' . $machineid);
                $data = Cache::remember(self::$prefix . '_aux_prices_.' . $machineid, now()->addMinutes(15)
                    , function () use ($machineid) {

                        $recs = contract_price::from('contract_prices as cp')
                            ->join('contracts as c', 'c.id', 'cp.contractid')
                            ->where('c.signed', 1)
                            ->where('cp.sysobjid', 482)
                            ->where('cp.objid', $machineid)
                            ->select('cp.price', 'cp.unitcode', 'c.docnum', 'c.docdate', 'c.name')
                            ->orderBy('c.created_at', 'asc')
                            ->skip(0)->take(4)
                            ->get();

                        $tstr = "";
                        $i = 0;
                        foreach ($recs as $rec) {
                            $i++;
                            if ($i > 1) {
                                $tstr = $tstr . ", <br>";
                            }
                            if ($i > 3) {
                                $tstr = $tstr . " ...";
                                break;
                            }
                            $tstr = $tstr . number_format($rec->price, 2) . ' (договор №' . $rec->docnum . ' от ' . $rec->docdate . ')';
                        }
                        $cnt = contract_price::from('contract_prices as cp')
                            ->join('contracts as c', 'c.id', 'cp.contractid')
                            ->where('c.signed', 1)
                            ->where('cp.sysobjid', 482)
                            ->where('cp.objid', $machineid)
                            ->count();

                        return ['sample' => $tstr, 'reccount' => $cnt ?? 0];
                    });
                if (isset($data))
                    array_push($info,
                        ['name' => "Цены",
                            'route' => 'machine_rqsts.index',
                            'sample' => $data['sample'],
                            'reccount' => $data['reccount'],
                            'btn-class' => 'bg-warning'
                        ]
                    );
            }
            //--------------------------------------------------------------

            //Группы ------------------------------------
            $ttt = Cache::remember('grptypes_count_.' . self::$sysobjid, now()->addMinutes(25)
                , function () use ($machineid) {

                    return grptype::where('forsysobjid', self::$sysobjid)->where('active', 1)->count();
                });

            if ($ttt > 0) {

                $data = Cache::remember(self::$prefix . '_aux_groups_.' . $machineid, now()->addMinutes(25)
                    , function () use ($machineid) {

                        $recs = group::from('groups as g')
                            ->join('grptypes as t', 't.id', '=', 'g.grptypeid')
                            ->whereExists(function ($query) use ($machineid) {
                                $query->select(DB::raw(1))
                                    ->from('grpitems as i')
                                    ->whereraw('i.grpid = g.id')
                                    ->where('i.sysobjid', self::$sysobjid)
                                    ->where('i.objid', $machineid);
                            })
                            ->selectraw('concat(t.name,": <b>", g.name, "</b>") as name')
//                ->orderBy('t.ordr', 'asc')
//                ->orderBy('g.ordr', 'asc')
                            ->skip(0)->take(4)
                            ->get();

                        $tstr = "";
                        $i = 0;
                        foreach ($recs as $rec) {
                            $i++;
                            if ($i > 1) {
                                $tstr = $tstr . ", ";
                            }
                            if ($i > 3) {
                                $tstr = $tstr . " ...";
                                break;
                            }
                            $tstr = $tstr . $rec->name;
                        }
                        $cnt = grpitem::where('active', 1)
                            ->where('sysobjid', self::$sysobjid)
                            ->where('objid', $machineid)
                            ->selectraw('count(*) as cnt')
                            ->first();

                        return ['sample' => $tstr, 'reccount' => $cnt ? $cnt->cnt : 0];
                    });
                if (isset($data))
                    array_push($info,
                        ['name' => "группы", 'route' => 'org_groups.edit', 'sample' => $data['sample'],
                            'reccount' => $data['reccount'],
                            'btn-class' => 'btn-warning'
                        ]
                    );
            }
            // -------------------------------------------------------------------------------

            //Связи с внешними системами
            if (1 == 0) {
                if (usrsysright::isUserHasRightByCode($userid, 'objextids.read')) {
                    $recs = objextid::from('objextids as oi')
                        ->join('extsystems as es', 'es.id', 'oi.extsysid')
                        ->where('oi.objid', $machineid)
                        ->where('oi.sysobjid', self::$sysobjid)
                        ->selectraw('concat(es.name, ": ", oi.extid) as name')
                        ->orderBy('oi.created_at', 'asc')
                        ->skip(0)->take(4)
                        ->get();

                    $tstr = "";
                    $i = 0;
                    foreach ($recs as $rec) {
                        //dd($rec->name);
                        $i++;
                        if ($i > 1) {
                            $tstr = $tstr . ", ";
                        }
                        if ($i > 3) {
                            $tstr = $tstr . " ...";
                            break;
                        }
                        $tstr = $tstr . $rec->name;
                    }
                    $cnt = objextid::where('objid', $machineid)->where('sysobjid', self::$sysobjid)->count();

                    array_push($info,
                        ['name' => 'внеш. системы', 'route' => 'machine_extids.index', 'sample' => $tstr, 'reccount' => $cnt]
                    );
                }
            }

        }
        return $info;
    }


    static public function lstActive($mchntypeid = null)
    {
        //Cache::forget(self::$prefix . '_lstTypes_' . $mchntypeid);
        $data = Cache::remember(self::$prefix . '_lstActive_' . $mchntypeid, now()->addMinutes(15)
            , function () use ($mchntypeid) {
                $lst = self::select('id', 'name')
                    ->where('active', 1);
                if (isset($mchntypeid))
                    $lst = $lst->where('mchntypeid', $mchntypeid);

                $lst = $lst->orderBy('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function lstActiveByOperCode($opercode)
    {
        //Cache::forget(self::$prefix . '_lstActiveByOperCode_' . $opercode);
        $data = Cache::remember(self::$prefix . '_lstActive_' . $opercode, now()->addMinutes(15)
            , function () use ($opercode) {
                $lst = self::select('id', 'name')
                    ->where('active', 1);
                if (isset($opercode))
                    $lst = $lst->where('opercodes', 'like', '%' . $opercode . '%');

                $lst = $lst->orderBy('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }


    static public function activePrices($machineid)
    {

        $data = Cache::remember(self::$prefix . '_activePrices_' . $machineid, now()->addMinutes(15)
            , function () use ($machineid) {
                $recs = contract_price::from('contract_prices as cp')
                    ->join('contracts as c', 'c.id', 'cp.contractid')
                    ->where('c.signed', 1)
                    ->where('c.active', 1)
                    ->where('cp.sysobjid', 482)
                    ->where('cp.objid', $machineid)
                    ->select('cp.id as priceid', 'cp.contractid', 'cp.price', 'cp.unitcode', 'c.docnum', 'c.docdate', 'c.name')
                    ->orderBy('c.created_at', 'asc')
                    ->get();
                return $recs;
            }
        );
        return $data;

    }

    static public function contrOrgs($machineid)
    {
        Cache::forget(self::$prefix . '_contrOrgs_' . $machineid);
        $data = Cache::remember(self::$prefix . '_contrOrgs_' . $machineid, now()->addMinutes(15)
            , function () use ($machineid) {
                $recs = mchncontrorg::from('mchncontrorgs as co')
                    ->leftjoin('orgs as o', 'o.id', 'co.orgid')
                    ->leftjoin('contracts as c', 'c.id', 'co.contractid')
                    ->where('co.active', 1)
                    ->where('co.machineid', $machineid)
                    ->select('co.id', 'co.begdate', 'co.enddate', 'co.active', 'co.orgid', 'o.name as orgname', 'co.contractid', 'c.docnum', 'c.docdate')
                    ->orderBy('co.begdate', 'desc')
                    ->get();
                return $recs;
            }
        );
        return $data;

    }

    static public function search_cond($params)
    {

        $sc = "1=1";

        //пользователь ДОЛЖЕН иметь доступ к категории информации, для того, чтобы работать с ней
        $userid = \Auth::user()->id;
//        if (!usrsysright::isUserHasRightByCode_cached($userid, 'acs.admin'))
//            $sc .= " and exists (select 1 from user_acs as uac where uac.acsid=m.acsid and uac.userid={$userid})";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'orgid') {
                        $sc .= " and m.orgid={$val}";

                    } elseif ($key == 'mchntypeid') {
                        $sc .= " and m.mchntypeid={$val}";

                    } elseif ($key == 'active') {
                        $sc .= " and m.active={$val}";

                    } elseif ($key == 's_name') {
                        $sc .= " and concat(m.regnum,' ',m.name) like '%{$val}%'";

                    } elseif ($key == 'in_cursias') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from cursias as crs where crs.machineid=m.id)";

                    } elseif ($key == 'in_mchn_raids') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from mchn_raids as mr where mr.machineid=m.id)";

                    } elseif ($key == 'in_fuelcards') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from fuelcards as fc where fc.ref_machineid=m.id)";

                    } elseif ($key == 'in_fuelcard_pays') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from fuelcard_pays as fcp where fcp.machineid=m.id)";

                    } elseif ($key == 'opertypeid') {
                        $sc .= " and exists(select 1 from mchn_opertypes as mot where mot.machineid=m.id and mot.opertypeid={$val})";

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;
    }


    static public function lstFor($params)
    {
        //2021-04-08 SNS. универсальный конструктор массива с id, name
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"


        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            if (1 == 1) {
                $lst = self::from('machines as m')
                    ->whereRaw($sc)
                    ->select('id', db::raw("concat(m.regnum, ' - ', m.name ) as name") )
                    ->orderby('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();
            } else {
                //Временно - 2021-08-03 - убрать через месяц
                $lst = self::from('machines as m')
                    ->leftJoin('orgs as o', 'o.id', 'm.orgid')
                    ->whereRaw($sc)
                    ->select('m.id', 'm.name', 'm.regnum', 'm.orgid', 'o.name as orgname')
                    ->get();
            }
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей machines
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'clp.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['m.name', 'asc'], ['m.regnum', 'asc']];

            $recs = self::from('machines as m')
                ->Join('orgs as o', 'o.id', 'm.orgid')
                ->whereRaw($sc)
                ->select($fields);

            foreach ($sorts as $sort) {
                $recs = $recs->orderBy($sort[0], $sort[1] ?? 'asc');
            }

            $recs = $recs->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }


    public static function import_001($file, $rec)
    {
        //Импорт счета на оплату из xlsx-файла в формате ___

        $userid = \Auth::user()->id;
        $result = new Result();

        //$fileuri = "/home/vagrant/code/basco/storage/app/public/files/879/1/Ведомость ресурсов материалы Отопление ИТП.xlsx";
        //Excel::import(new invoiceImport(), $fileuri, null, \Maatwebsite\Excel\Excel::XLSX);

        //Excel::import(new invoiceImport(), request()->file('doc'));
//            $collection = Excel::toCollection(new invoiceImport, request()->file('doc'));
//            dd($collection);

        //$array = Excel::toArray(new invoiceImport, request()->file('doc'));
        $array = Excel::toArray(new invoiceImport, $file);
        $array = $array[0];
        //dd($array);

        //Названия полей ожидаем в первой строке
        $fields = $array[0];
        if (!(
            in_array('regnum', $fields)
            and in_array('name', $fields)
            and in_array('orgname', $fields)
        )) {
            $result->err = 1;
            $result->msg = 'Файл должен содержать колонки "regnum", "name", "orgname"!';
            $rec->result = $result;
            return $rec;
        }

        //dd($fields,count($array));
        $fld_idx = array_flip($fields);
        //dd($fld_idx);

        $items_add_cnt = 0; //кол-во новых записей
        $items_upd_cnt = 0; //кол-во обновленных записей

        for ($i = 1; $i < count($array); $i++) {
            $regnum = $array[$i][$fld_idx['regnum']];
            $name = $array[$i][$fld_idx['name']];
            $typename = $array[$i][$fld_idx['typename'] ?? ''] ?? '';
            $orgname = $array[$i][$fld_idx['orgname'] ?? ''] ?? '';
            $other = $array[$i][$fld_idx['other'] ?? ''] ?? '';

            //dd($regnum, $name, $typename, $orgname, $other);


            if (isset($regnum)) {

                //определим id тип техники
                $mchntypeid = objextid::objid_by_extsysid_extid(9, 481, $typename) ?? 13;
                //dd($typename, $mchntypeid);

                //определим id владельца техники
                $orgid = objextid::objid_by_extsysid_extid(9, 111, $orgname) ?? 21;
                //dd($ownorgname, $ownorgid);

                $machine = self::where('regnum', $regnum)->first();
                if (!isset($machine)) {
                    $machine = new self([
                        'regnum' => $regnum
                    ]);
                    ++$items_add_cnt;
                } else
                    ++$items_upd_cnt;

                $machine->name = $name;
                $machine->mchntypeid = $mchntypeid;
                $machine->orgid = $orgid;
                //dd($machine);
                $machine->save();

                //continue;
            }
        }

        $result->msg .= "- добавлено записей: {$items_add_cnt}" . PHP_EOL;
        $result->msg .= "- изменено записей: {$items_upd_cnt}" . PHP_EOL;

        $rec->result = $result;
        //--------------------------------------------------------------------------

        return $rec;
    }


}
