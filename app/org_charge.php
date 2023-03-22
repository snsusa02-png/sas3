<?php

namespace App;

use App\Imports\invoiceImport;
use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\Result;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class org_charge extends Model
{
    //
    use DeleteTrait;
    use FilesTrait;

    //protected $fillable = ["orgid"];
    protected $guarded = [];

    protected $table = 'org_charges';

    static public $prefix = 'org_charges';
    static public $sysobjid = 1211;

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function chargetype()
    {
        return $this->hasOne(chargetype::class, 'id', 'chargetypeid')
            ->withDefault();
    }

    public function features()
    {
        return $this->hasMany(obj_feature::class, 'objid', 'id')
            ->where('obj_features.sysobjid', self::$sysobjid);
    }

    public function salaries()
    {
        return $this->hasMany(stf_salary::class, 'staffid', 'id')
            ->orderby('wrkbegdate', 'desc');
    }

    public function getNamePostAttribute()
    {
        if (isset($this->id)) {
            return $this->name . ', ' . $this->postname;
        } else
            return null;
    }

    public function getshortfioAttribute()
    {
        $rslt = null;
        if (isset($this->lname)) {
            $rslt = $this->lname;
            if (isset($this->fname)) {
                $rslt = $rslt . ' ' . mb_substr($this->fname, 0, 1) . '.';
                if (isset($this->mname))
                    $rslt = $rslt . mb_substr($this->mname, 0, 1) . '.';
            }
        }
        return $rslt;
    }

    static public function getFIO($staffid)
    {
        $fio = "";
        if (!is_null($staffid)) {
            $staff = static::where('id', $staffid)->select("fname", "mname", "lname")->first();
            if ($staff->exists()) {
                $fio = $staff['lname'] . " " . $staff['fname'];
                if (mb_strlen($staff['mname']) > 0) {
                    $fio .= " " . $staff['mname'];
                }
            }
        }
        return $fio;
    }

    public function getflNameAttribute()
    {
        //Возвращает Сергей Шевченко
        $rslt = null;
        if (isset($this->id)) {
            if (isset($this->fname)) {
                $rslt = $this->fname;
                if (isset($this->lname)) {
                    $rslt = $rslt . ' ' . $this->lname;
                }
            }
        }
        return $rslt;
    }

    public function getInfoAttribute()
    {
        $rslt = null;
        if (isset($this->id)) {
            $rslt = $this->name . ', ' . $this->postname;
        }
        return $rslt;
    }

    static public function getUserInfo($staffid)
    {
        return \App\User::whereStaffid($staffid)
            ->select(\DB::raw("trim(ifnull(concat(lname,' ',fname,' ',mname),name)) as fiofull,
                                trim(ifnull(concat(lname,' ',substring(fname,1,1),'. ',substring(mname,1,1),'.'),name)) as fioshort,
                                trim(email) as email,
                                trim(ifnull(concat(fname,' ',mname),name)) as greeting"))
            ->first();
    }

    static public function getUserID($staffid)
    {
        return self::find($staffid)->userid ?? null;
    }

    //Сотрудники контрагента - коллекция моделей
    static public function getOrgStaffList($orgid)
    {
        return static::from('orgstaff as os')
            ->where('os.orgid', '=', $orgid)
            //->where('os.id', '!=', 1)
            ->leftjoin('users as u', 'u.id', '=', 'os.userid')
            ->orderBy("os.active", 'desc')
            ->orderBy("os.lname", 'asc')
            ->orderBy('os.fname', 'asc')
            ->select('os.id', 'os.lname', 'os.fname', 'os.mname', 'os.postname', 'os.email', 'os.phone', 'os.active', 'os.userid')
            ->with('photo')
            ->with('user')
            ->get();
    }

    //Сотрудники контрагента - массив
    static public function listActiveForOrg($orgid)
    {
        return static::from('orgstaff as os')
            ->where('os.orgid', '=', $orgid)
            ->orderBy("os.lname", 'asc')
            ->orderBy('os.fname', 'asc')
            ->select('id', DB::raw("concat(lname,' ', fname, ' ', mname, ', ', ifnull(postname,'-')) as name"))
            ->get()->pluck('name', 'id')->toArray();
    }

    static public function UsersForStaffID($StaffID)
    {
        //Пользователи, связанные с заданным сотрудником

        return User::from('users as u')
            ->join('orgstaff as os', 'os.userid', 'u.id')
            ->where('os.id', $StaffID)
            ->select('u.*')
            ->get();
    }

    static public function staffByOrgIDUserID($orgid, $userid)
    {
        //Сотрудник компании $orgid, связанный с пользователем $userid
        return orgstaff::where(['orgid' => $orgid, 'userid' => $userid])->first();
    }

    static public function listUsersByFIO($lname, $fname, $mname)
    {
        //массив варианты пользователей с подходящим ФИО

        return User::from('users as u')
            ->where('u.lname', $lname)
            ->where('u.fname', $fname)
            ->where('u.mname', $mname)
            ->select('u.id', DB::raw("concat(u.lname,' ', u.fname,' ', u.mname) as name"))
            ->orderby('u.lname')->orderby('u.fname')
            ->get()->pluck('name', 'id')->toArray();
    }

    static public function userOrgID()
    {
        $orgid = \Auth::user()->curorgid ?? 0;
//        echo 'userOrgID: orgid='.$orgid;
        if (!isset($orgid)) {
            $staffid = \Auth::user()->StaffID;
            if (isset($staffid)) {
                $staff = orgstaff::find($staffid);
                $orgid = $staff->orgid;
            }
        }
        return $orgid;
    }

    public function staff_fio($short = false)
    {
        //Возвращает ФИО текущего сотрудника,
        // $short - Иванов И.А.
        // иначе - Иванов Иван Андреевич

        $fio = null;
        if (!isset($short)) $short = false;

        $rec = orgstaff::
//        selectraw('concat(lname," ",fname," ",ifnull(mname," ")) as name')
        select('lname', 'fname', 'mname')
            ->find($this->id);
        if (isset($rec)) {
            $fio = $rec->lname;
            if ($short) {
                if (isset($rec->fname)) {
                    $fio = $fio . ' ' . mb_substr($rec->fname, 0, 1) . '.';
                    if (isset($rec->mname)) {
                        $fio = $fio . mb_substr($rec->mname, 0, 1) . '.';
                    }
                }
            } else {
                if (isset($rec->fname)) {
                    $fio = $fio . ' ' . $rec->fname;
                    if (isset($rec->mname)) {
                        $fio = $fio . ' ' . $rec->mname;
                    }
                }
            }
        }

        return $fio;
    }

    static public function search_cond($params)
    {
        $sc = "1=1";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 's_name' or $key == 'name') {
                        $sc = $sc . " and concat(ct.name,' ',oc.notes) like '%" . mb_strtoupper($val) . "%'";

                    } elseif ($key == 's_orgflagid') {
                        $sc .= " and exists(select 1 from objflags f where f.sysobjid=111 and f.objid=os.orgid and f.flagtypeid={$val})";

                    } elseif ($key == 's_file_doctypeid') {
                        $tsysobjid = self::sysobjid;
                        $sc = $sc . " and exists (select 1 from objfiles as f where f.sysobjid={$tsysobjid}
                                        and f.objid=os.id and f.doctypeid={$val})";

                    } elseif ($key == 'orgid') {
                        $sc .= " and oc.orgid={$val}";

                    } elseif ($key == 'active' or $key == 's_active') {
                        $sc .= " and ifnull(oc.active,0) = '{$val}'";

                    } elseif ($key == 'active_or_current') {
                        if (isset($val))
                            $sc .= " and (oc.active=1 or oc.id={$val})";
                        else
                            $sc .= " and oc.active=1";

                    } elseif ($key == 'period_not_once') {
                        $sc .= " and if(oc.charge_period,'1',oc.charge_period) <> '1'";

                    } elseif ($key == 'in_cursias') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from cursias as crs where crs.staffid=os.id)";

                    } elseif ($key == 'in_documents') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from obj_staffs as ojs where ojs.sysobjid=1701 and ojs.staffid=os.id)";

                    } elseif ($key == 'driver_in_mchn_raids') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from mchn_raids as mr where mr.driverid=os.id)";

                    } elseif ($key == 'dispatcher_in_mchn_raids') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from mchn_raids as mr where mr.disp_staffid=os.id)";

                    } elseif ($key == 'dispatcher_in_mr_opers') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from mr_opers as mro where mro.disp_staffid=os.id)";

                    } elseif ($key == 'no_signature_for_sysobj') {
                        //нет требующейся подписи на хранимом образе документа
                        $sc .= " and exists( select 1 from obj_staffs as ojs where ojs.sysobjid={$val} and ojs.staffid=os.id and ojs.signed=0 )";

                    } elseif ($key == 'in_driver_works') {
                        //
                        $sc .= " and exists( select 1 from driver_works as dw where dw.staffid=os.id)";

                    } elseif ($key == 'in_org_curators_now') {
                        //сотрудник должен быть куратором организации
                        $sc .= " and exists( select 1 from org_curators as oc where oc.staffid=os.id
                            and oc.active=1 and now() between oc.begdt and ifnull(oc.enddt,now()) )";
                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-04-29 SNS. универсальный конструктор массива с id, name сотрудников
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('org_charges as oc')
                ->join('chargetypes as ct', 'ct.id', 'oc.chargetypeid')
                ->whereRaw($sc)
                ->select('oc.id', db::raw("trim(concat('[', if((ct.dir<0), '-', '+'), '] ', ct.name, ', 1/' ,  if(oc.charge_period is null, '1', oc.charge_period), ' =' , oc.charge_sum, ' руб')) as tname"))
                ->orderBy('tname', 'desc')
                ->get()->pluck('tname', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-10-06 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget('lstFor_' . $hash);
            return Cache::remember(self::$prefix . '_lstFor_' . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
        } else
            return null;
    }


    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей orgdeps
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'os.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['ct.dir', 'desc'], ['ct.name', 'asc']];

            $recs = self::from('org_charges as oc')
                ->Join('chargetypes as ct', 'ct.id', 'oc.chargetypeid')
//                ->leftJoin('orgposts as op', 'op.id', 'os.postid')
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

        $array = Excel::toArray(new invoiceImport, $file);
        $array = $array[0];
        //dd($array);

        //Названия полей ожидаем в первой строке
        $fields = $array[0];
        if (!(
            in_array('lname', $fields)
            and in_array('fname', $fields)
            and in_array('mname', $fields)
            and in_array('orgname', $fields)
        )) {
            $result->err = 1;
            $result->msg = 'Файл должен содержать колонки "lname", "fname", "mname", "orgname"!';
            $rec->result = $result;
            return $rec;
        }

        //перевернем колонки
        $fld_idx = array_flip($fields);

        $items_add_cnt = 0; //кол-во новых записей
        $items_upd_cnt = 0; //кол-во обновленных записей

        for ($i = 1; $i < count($array); $i++) {

            $lname = $array[$i][$fld_idx['lname']];
            $fname = $array[$i][$fld_idx['fname']];
            $mname = $array[$i][$fld_idx['mname'] ?? ''] ?? '';
            $orgname = $array[$i][$fld_idx['orgname'] ?? ''] ?? '';
            //dd($regnum, $name, $typename, $orgname, $other);

            if (isset($lname) and isset($fname) and isset($mname)) {

                //определим id владельца техники
                $orgid = objextid::objid_by_extsysid_extid(9, 111, $orgname) ?? 21;
                //dd($orgname, $orgid);

                //Ключем считаем полное ФИО
                $orgstaff = self::where([
                    'lname' => $lname,
                    'fname' => $fname,
                    'mname' => $mname,
                ])->first();

                if (!isset($orgstaff)) {

                    $orgstaff = new self([
                        'lname' => $lname,
                        'fname' => $fname,
                        'mname' => $mname,
                        'name' => $lname . ' ' . $fname . ' ' . $mname,
                    ]);
                    ++$items_add_cnt;
                } else
                    ++$items_upd_cnt;

                $orgstaff->orgid = $orgid;
                $orgstaff->postname = $array[$i][$fld_idx['postname'] ?? ''] ?? '';
                //dd($orgstaff);
                $orgstaff->save();

                //Обработаем Признаки
                if (isset($fld_idx['flags'])) {
                    $flags = $array[$i][$fld_idx['flags']] ?? null;
                    if (isset($flags)) {
                        $flags = explode(',', $flags);
                        if (is_array($flags) and count($flags) > 0) {
                            foreach ($flags as $flag) {
                                objflag::AddObjFlag(self::$sysobjid, $orgstaff->id, $flag);
                            }
                        }
                    }
                }


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
