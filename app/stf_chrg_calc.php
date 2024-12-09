<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class stf_chrg_calc extends Model
{
    use DeleteTrait;

    static public $prefix = 'stf_chrg_calc';
    static public $sysobjid = 1213;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')
            ->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')
            ->withDefault();
    }

    public function ref_sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'ref_sysobjid')
            ->withDefault();
    }

    public function orgstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid')->withDefault();;
    }

    public function org_charge()
    {
        return $this->hasOne(org_charge::class, 'id', 'orgchargeid')
            ->withDefault()
            ->with('chargetype');
    }

    static public function isLocked($id)
    {
        //Попадает ли нужная запись в заблокированный период?

        $lockdate = sysobj_lockdate::where('sysobjid', self::$sysobjid)->select('lock_before')->first()->lock_before ?? null;
        if (isset($lockdate)) {
            $rec = self::find($id);
            if (isset($rec)) {
                return ($rec->wrkbegdate < $lockdate);
            }
        }
        return false;
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

                    if ($key == 'stf_name') {
                        $sc = $sc . " and concat(os.lname,' ',ifnull(os.fname,''),' ',ifnull(os.mname,'')) like '%" . mb_strtoupper($val) . "%'";

                    } elseif ($key == 'chargetype_name') {
                        $sc = $sc . " and concat(ct.name,' ',ifnull(oc.notes,' ')) like '%" . mb_strtoupper($val) . "%'";

                    } elseif ($key == 'charge_dir') {
                        $sc = $sc . " and ct.dir=$val";

                    } elseif ($key == 'orgid' or $key == 's_orgid') {
                        $sc .= " and os.orgid={$val}";

                    } elseif ($key == 's_dir') {
                        $sc .= " and ct.dir={$val}";

                    } elseif ($key == 's_ym') {
                        $date = date_create($val . '-01')->format('Y-m-d');
                        //dd($date);

                        $date = $date ?? date_create()->format('d-m-Y');
                        $begdate = date_create($date)->format('Y-m-01');   //Первый день месяца
                        $enddate = date_create($date)->format('Y-m-t');    //Последний день месяца

                        $sc .= " and scc.forbegdate <= '" . date_create($enddate)->format('Y-m-d') . "'"
                             . " and scc.forEndDate >= '" . date_create($begdate)->format('Y-m-d') . "'";
                    }
                }

            }
        }
        //Log::info($sc);
        return $sc;
    }

}
