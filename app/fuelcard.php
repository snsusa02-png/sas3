<?php

namespace App;

use App\Imports\invoiceImport;
use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\Result;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class fuelcard extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'fuelcards';
    static public $sysobjid = 561;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function suporg()
    {
        return $this->hasOne(org::class, 'id', 'suporgid')
            ->withDefault();
    }

    public function ref_machine()
    {
        return $this->hasOne(machine::class, 'id', 'ref_machineid')
            ->withDefault();
    }

    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = '/ №' . $this->num . ' - ' . $this->name . '.  ' . $this->ownorg->name;
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


    static public function activePrices($fuelcardid)
    {

        $data = Cache::remember(self::$prefix . '_activePrices_' . $fuelcardid, now()->addMinutes(15)
            , function () use ($fuelcardid) {
                $recs = contract_price::from('contract_prices as cp')
                    ->join('contracts as c', 'c.id', 'cp.contractid')
                    ->where('c.signed', 1)
                    ->where('c.active', 1)
                    ->where('cp.sysobjid', 482)
                    ->where('cp.objid', $fuelcardid)
                    ->select('cp.id as priceid', 'cp.contractid', 'cp.price', 'cp.unitcode', 'c.docnum', 'c.docdate', 'c.name')
                    ->orderBy('c.created_at', 'asc')
                    ->get();
                return $recs;
            }
        );
        return $data;

    }

    static public function contrOrgs($fuelcardid)
    {
        Cache::forget(self::$prefix . '_contrOrgs_' . $fuelcardid);
        $data = Cache::remember(self::$prefix . '_contrOrgs_' . $fuelcardid, now()->addMinutes(15)
            , function () use ($fuelcardid) {
                $recs = mchncontrorg::from('mchncontrorgs as co')
                    ->leftjoin('orgs as o', 'o.id', 'co.orgid')
                    ->leftjoin('contracts as c', 'c.id', 'co.contractid')
                    ->where('co.active', 1)
                    ->where('co.fuelcardid', $fuelcardid)
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

                    if ($key == 'ownorgid') {
                        $sc .= " and fc.ownorgid={$val}";

                    } elseif ($key == 'active') {
                        $sc .= " and fc.active={$val}";

                    } elseif ($key == 's_name') {
                        $sc .= " and concat(m.num,' ',m.name) like '%{$val}%'";

                    } elseif ($key == 'in_fuelcard_pays') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from fuelcard_pays as fcp where fcp.cardid=fc.id)";

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
                $lst = self::from('fuelcards as fc')
                    ->whereRaw($sc)
                    ->select('id', 'num as name')
                    ->orderby('num')
                    ->get()
                    ->pluck('name', 'id')->toArray();
            }
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-12-04 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget('lstFor_' . self::$prefix . $hash);
            return Cache::remember('lstFor_' . self::$prefix . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей fuelcards
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'clp.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['fc.name', 'asc'], ['fc.num', 'asc']];

            $recs = self::from('fuelcards as fc')
                ->Join('orgs as oo', 'oo.id', 'fc.ownorgid')
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

                $fuelcard = self::where('regnum', $regnum)->first();
                if (!isset($fuelcard)) {
                    $fuelcard = new self([
                        'regnum' => $regnum
                    ]);
                    ++$items_add_cnt;
                } else
                    ++$items_upd_cnt;

                $fuelcard->name = $name;
                $fuelcard->mchntypeid = $mchntypeid;
                $fuelcard->orgid = $orgid;
                //dd($fuelcard);
                $fuelcard->save();

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
