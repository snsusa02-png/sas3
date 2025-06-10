<?php

namespace App;

use App\Events\notifyEvent;
use App\Imports\invoiceImport;
use App\objextid;
use App\Traits\FilesTrait;
use App\Traits\Result;
use App\Traits\snsTrait;
use App\Traits\StringUtil;
use App\usrsysright;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

use App\Http\Middleware\IStock;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Nwidart\Modules\Facades\Module;

use App\User;

use App\Traits\DeleteTrait;
use Illuminate\Support\Facades\Log;
use Goutte\Client;


class org extends Model
{
    use DeleteTrait;
    use FilesTrait;
    use snsTrait;

    static public $prefix = 'orgs';
    static public $sysobjid = 111;

    //protected $fillable = ["name", "active", "created_by", "updated_by"];

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];


    static public $kinds = [1 => 'Ю/Л', 2 => 'ИП', 3 => 'Ф/Л'];


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function boss()
    {
        return $this->hasOne(orgstaff::class, 'id', 'boss_staffid')->withDefault();
    }

    public function ca()
    { // Chief Accountant
        return $this->hasOne(orgstaff::class, 'id', 'ca_staffid')->withDefault();
    }

    public function short_name()
    { // Короткое название организации
        return $this->hasOne(org_name::class, 'orgid', 'id')
            ->where('nametypeid', 2);
    }

    public function post_address()
    { // Почтовый адрес
        return $this->hasOne(obj_address::class, 'objid', 'id')
            ->where('obj_addresses.sysobjid', self::$sysobjid)
            ->where('obj_addresses.addresstypeid', 3)
            ->withDefault();
    }

    public function contact_phone_()
    { // Контактный телефон
        return $this->hasOne(obj_contact::class, 'objid', 'id')
            ->where('obj_contacts.sysobjid', self::$sysobjid)
            ->where('contacttypeid', 1)
            ->where('active', 1)
            ->withDefault();
    }

    public function contact_email_()
    { // Контактная электронная почта
        return $this->hasOne(obj_contact::class, 'objid', 'id')
            ->where('obj_contacts.sysobjid', self::$sysobjid)
            ->where('obj_contacts.contacttypeid', 2)
            ->where('active', 1)
            ->withDefault();
    }


    //связь с пользователями-кураторами
    public function CuratorUsers()
    {
        return $this->hasMany(org_curator::class, 'orgid', 'id')->with('refitem');
    }

    public function acnts()
    {
        return $this->hasMany(org_acnt::class, 'orgid', 'id');
    }

    public function places()
    {
        return $this->hasMany(org_place::class, 'orgid', 'id');
    }

    public function assoc_members()
    {//Участник ассоциаций
        return $this->hasMany(assoc_member::class, 'orgid', 'id');
    }

    public function orgdeps()
    {
        return $this->hasMany(orgdep::class, 'orgid', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function getInfoAttribute()
    {
        return "{$this->name}" . (($this->inn) ? " ({$this->inn})" : "");
    }


    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('firstread_at');
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }

    public static function active()
    {
        return static::where('active', true)->get();
    }

    static public function getName($orgid)
    {
        return static::where("id", $orgid)->value("name");
    }

    static public function AuxInfo($org)
    {
        $info = [];
        $userid = \Auth::user()->id;

        if (isset($org->id)) {
            $orgid = $org->id;

            //Представители клиента
            if (1 == 1) {
                $data = Cache::remember('org_aux_users_.' . $orgid, now()->addMinutes(25)
                    , function () use ($orgid) {

                        $recs = userorg::from('userorgs as uo')
                            ->join('users as u', 'u.id', 'uo.userid')
                            ->where('uo.orgid', $orgid)
                            ->where('uo.active', 1)
//                            ->where('u.active', 1)
//                            ->selectraw('concat(u.lname," ", left(u.fname,1),".", left(u.mname,1)) as name')
                            ->select('u.name')
                            ->orderBy('uo.created_at', 'asc')
                            ->orderBy('uo.id', 'asc')
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
                        $cnt = userorg::where(['orgid' => $orgid, 'active' => 1])->count();

                        return ['sample' => $tstr, 'reccount' => $cnt];
                    });
                if (isset($data))
                    array_push($info,
                        ['name' => "Представители",
//                            'route' => 'org_users.index',
                            'route' => null,
                            'sample' => $data['sample'],
                            'reccount' => $data['reccount'],
                            'btn-class' => 'bg-light'
                        ]
                    );
            }
            //--------------------------------------------------------------

            //Сотрудники
            if (1 == 1) {
                $data = Cache::remember('org_aux_staff_.' . $orgid, now()->addMinutes(25)
                    , function () use ($orgid) {

                        $recs = orgstaff::where('orgid', $orgid)
                            ->where('active', 1)
                            ->selectraw('concat(lname," ", left(fname,1),".", left(mname,1), " (", IFNULL(postname, "-"), ")") as name')
                            ->orderBy('created_at', 'asc')
                            ->orderBy('id', 'asc')
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
                        $cnt = orgstaff::where(['orgid' => $orgid, 'active' => 1])->count();

                        return ['sample' => $tstr, 'reccount' => $cnt];
                    });
                if (isset($data))
                    array_push($info,
                        ['name' => "сотрудники",
                            'route' => 'org_staff.index',
                            'sample' => $data['sample'],
                            'reccount' => $data['reccount'],
                            'btn-class' => 'btn-success'
                        ]
                    );
            }
            //--------------------------------------------------------------

            //Скидки
            if (1 == 0) {
                $data = Cache::remember('org_aux_discounts_.' . $orgid, now()->addMinutes(25)
                    , function () use ($orgid) {

                        $recs = orgItmDiscount::from('orgitmdiscounts as d')
                            ->where('d.orgid', $orgid)
                            ->where('d.active', 1)
                            ->join('itmtypes as it', 'it.id', '=', 'd.itmtypeid')
                            ->selectraw('concat(it.name,": ", d.dscntpcnt, "%") as name')
                            ->orderBy('it.ordr', 'asc')
                            ->orderBy('d.created_at', 'asc')
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
                        $cnt = orgItmDiscount::where(['orgid' => $orgid, 'active' => 1])->count();

                        return ['sample' => $tstr, 'reccount' => $cnt];
                    });
                if (isset($data))
                    array_push($info,
                        ['name' => "скидки",
                            'route' => 'org_discounts.index',
                            'sample' => $data['sample'],
                            'reccount' => $data['reccount'],
                            'btn-class' => 'btn-success'
                        ]
                    );
            }
            // -------------------------------------------------------------------------------

            //Поставляемые товары и актуальные цены
            if (1 == 1) {
                Cache::forget('org_ri_prices_.' . $orgid);
                $data = Cache::remember('org_ri_prices_.' . $orgid, now()->addMinutes(5)
                    , function () use ($orgid) {

                        $recs = ri_sup_price::from('ri_sup_prices as rop')
                            ->join('refitems as ri', 'ri.id', 'rop.refitmid')
                            //->join('itmtypes as it', 'it.id', '=', 'ri.itmtypeid')
                            ->where('rop.orgid', $orgid)
                            ->where('rop.active', 1)
                            ->whereRaw("curdate() between rop.begdate and ifnull(rop.enddate,curdate())")
                            ->selectRaw("concat(ri.name,' ',rop.price,' руб/', ri.unit) as name")
                            ->orderBy('rop.updated_at', 'desc')
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
                        $cnt = ri_sup_price::where(['orgid' => $orgid, 'active' => 1])->count();;

                        return ['sample' => $tstr, 'reccount' => $cnt];
                    });
                if (isset($data))
                    array_push($info,
                        ['name' => "товары/цены",
                            'route' => 'org_ri_prices.index',
                            'sample' => $data['sample'],
                            'reccount' => $data['reccount'],
                            'btn-class' => 'btn-info'
                        ]
                    );
            }
            // -------------------------------------------------------------------------------


            //Группы
            if (1 == 0) {
                $data = Cache::remember('org_aux_groups_.' . $orgid, now()->addMinutes(25)
                    , function () use ($orgid) {

                        $recs = group::from('groups as g')
                            ->join('grptypes as t', 't.id', '=', 'g.grptypeid')
                            ->whereExists(function ($query) use ($orgid) {
                                $query->select(DB::raw(1))
                                    ->from('grpitems as i')
                                    ->whereraw('i.grpid = g.id')
                                    ->where('i.sysobjid', 111)
                                    ->where('i.objid', $orgid);
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
                            ->where('sysobjid', 111)
                            ->where('objid', $orgid)
                            ->count();

                        return ['sample' => $tstr, 'reccount' => $cnt];
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

            if (!$org->isownorg) {
                //То, что имеет смысл для остальных компаний

                //Кураторы
                //Cache::forget('org_aux_curator_' . $orgid);
                $data = Cache::remember('org_aux_curator_' . $orgid, now()->addMinutes(15)
                    , function () use ($orgid) {

                        $recs = org_curator::from('org_curators as c')
                            ->join('orgstaff as u', 'u.id', '=', 'c.staffid')
                            ->where(['c.orgid' => $orgid, 'c.active' => 1])
                            ->whereRaw('now() between begdt and ifnull(enddt,now())')
                            ->select('u.name')
                            ->orderBy('c.created_at', 'asc')
                            ->orderBy('c.id', 'asc')
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
                        $cnt = org_curator::where(['orgid' => $orgid, 'active' => 1])
                            ->whereRaw('now() between begdt and ifnull(enddt,now())')
                            ->count();
                        return ['sample' => $tstr, 'reccount' => $cnt];
                    });
                if (isset($data))
                    array_push($info,
                        ['name' => "кураторы",
                            'route' => 'org_curators.index',
                            'sample' => $data['sample'],
                            'reccount' => $data['reccount'],
                            'btn-class' => 'btn-info'
                        ]
                    );
                // -------------------------------------------------------------------------------


                if (1 == 0) {
                    //Заказы
                    $data = Cache::remember('org_aux_orders_.' . $orgid, now()->addMinutes(25)
                        , function () use ($orgid) {

                            $recs = order::from('orders as o')
                                ->where('o.orgid', $orgid)
                                ->selectraw('concat("№",o.id," /", date_format(o.created_at, "%d.%m.%Y")) as name')
                                ->orderBy('o.created_at', 'asc')
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
                            $cnt = order::where('orgid', $orgid)->count();

                            return ['sample' => $tstr, 'reccount' => $cnt];
                        });
                    if (isset($data))
                        array_push($info,
                            ['name' => "заказы",
                                'route' => 'orders.for_org',
                                'sample' => $data['sample'],
                                'reccount' => $data['reccount'],
                                'btn-class' => 'btn-primary'
                            ]
                        );
                }
                // -------------------------------------------------------------------------------


                if (1 == 0) {
//                $mdl_Stock = Module::find('Stock');
                    $stock = resolve('App\Http\Middleware\IStock');
                    if (isset($stock) and $stock->active()) {

                        //Склады которые обслуживают клиента
                        $recs = $stock->auxinfo_org_wrhs($orgid);
                        $cnt = count($recs);

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
                            $tstr = $tstr . $rec;
                        }

                        array_push($info,
                            ['name' => "склады", 'route' => 'org_wrhs.index', 'sample' => $tstr,
                                'reccount' => $cnt,
                                'btn-class' => 'btn-secondary'
                            ]
                        );
                    }
                }
            }

            //Связи с внешними системами
            if (usrsysright::isUserHasRightByCode($userid, 'objextids.read')) {
                $recs = objextid::from('objextids as oi')
                    ->join('extsystems as es', 'es.id', 'oi.extsysid')
                    ->where('oi.objid', $orgid)
                    ->where('oi.sysobjid', 111)
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
                $cnt = objextid::where('objid', $orgid)->where('sysobjid', 111)->count();

                array_push($info,
                    ['name' => 'внеш. системы', 'route' => 'org_extids.index', 'sample' => $tstr
                        , 'reccount' => $cnt
                        , 'btn-class' => 'btn-secondary']
                );
            }

            //Предложения
            if (1 == 0) {
                $data = Cache::remember('org_offers__.' . $orgid, now()->addMinutes(25)
                    , function () use ($orgid) {

                        $recs = eritm_offer::where('suporgid', $orgid)
                            ->selectraw('concat(itmname, ": ", IFNULL(ord_price, "-")) as name')
                            ->orderBy('id', 'desc')
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
                        $cnt = eritm_offer::where('suporgid', $orgid)->count();

                        return ['sample' => $tstr, 'reccount' => $cnt];
                    });
                if (isset($data))
                    array_push($info,
                        ['name' => "предложения",
                            'route' => 'org_supoffers.index',
                            'sample' => $data['sample'],
                            'reccount' => $data['reccount'],
                            'btn-class' => 'btn-warning'
                        ]
                    );
            }
            //--------------------------------------------------------------

        }
        return $info;
    }

    static public function listUsed($params)
    {
        $orgs = org::from('orgs as o')
            ->select('name', 'id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('ri_compounds as ric')
                    ->whereRaw('ric.ownorgid = o.id');
            })
            ->orderBy('name')
            ->get()->pluck("name", "id")->prepend("-любой-", "");
        return $orgs;
    }

    static public function fullname_on_date($orgid, $on_date = null)
    {
        if (!isset($orgid))
            return false;

        $org = self::find($orgid);
        if (!isset($org))
            return false;

        //2021-07-21 SNS Пока нет org_names, сделаем по-простому
        return $org->fullname ?? $org->name;

    }

    static public function lstOwnOrgs()
    {
        //массив записей с организациями-продавцами (Владельцами)
        //Cache::forget('lstOwnOrgs');
        return Cache::remember('lstOwnOrgs', now()->addMinutes(25)
            , function () {
                return objflag::from('objflags as f')
                    ->join('orgs as o', 'o.id', '=', 'f.objid')
                    ->select('o.id', 'o.name')
                    ->where('f.flagtypeid', 12)
                    ->where('f.sysobjid', 111)
                    ->orderby('o.name')
                    ->get()->pluck("name", "id");//->prepend("", "");
            });
    }

    static public function lstOwnOrgsForUser($userid)
    {
        //массив записей с организациями-продавцами (Владельцами) для указанного пользователя (ограничение представительством)
        //Cache::forget('lstOwnOrgs_user_'.$userid);
        return Cache::remember('lstOwnOrgs_user_' . $userid, now()->addMinutes(5)
            , function () use ($userid) {
                return objflag::from('objflags as f')
                    ->join('orgs as o', 'o.id', '=', 'f.objid')
                    ->select('o.id', 'o.name')
                    ->where('f.flagtypeid', 12)
                    ->where('f.sysobjid', 111)
                    ->whereRaw("exists (select 1 from userorgs as uo where uo.orgid=o.id and uo.userid=" . $userid
                        . " and uo.active=1 and now() between uo.begdt and ifnull(uo.enddt,now()))")
                    ->orderby('o.name')
                    ->get()->pluck("name", "id")->toArray();
            });
    }

    static public function isOwnOrg($orgid)
    {
        //возвращает 1 если заданная организация является Собственной компанией (OwnOrg)
        return Cache::remember('isOwnOrg_' . $orgid, now()->addMinutes(25)
            , function () use ($orgid) {
                return objflag::IsSetObjFlag(111, $orgid, 12);
            });
    }

    public static function OwnOrgIDByOrgID($orgid)
    {
        //возвращает id организации продавца для заданной организации-покупателя
        // Временная схема - для упрощения пока считается, что покупатель может иметь
        // отношения только с одной организацией-продавцом (из холдинга)

        $ownorgid = orgdog::from('orgdogs as d')
            ->select('d.ownorgid')
            ->where('d.orgid', $orgid)
            ->where('d.active', 1)
            ->whereRaw('now() between begdate and ifnull(enddate,now())')
            ->first();
        if (isset($ownorgid)) {
            $ownorgid = $ownorgid->ownorgid;
        } else {
            //Договор не нашелся.
            //Возможно организация сама является продавцом?
            if (objflag::IsSetObjFlag(111, $orgid, 12)) {
                //Тогда она "обслуживает сама себя
                $ownorgid = $orgid;
            } else
                $ownorgid = null;
        }

        return $ownorgid;
    }

    public static function SetOwnOrgIDforOrgID($orgid, $ownorgid)
    {
        //сохраняет привязку организации-клиента к организации владельца
        //Пока - упрощенно считаем, что связь только однозначная (Один к одному)

        if (!org::isOwnOrg($orgid)) {

            if (!is_null($ownorgid)) {
                $rec = orgdog::from('orgdogs as d')
                    ->where('d.orgid', $orgid)
                    ->where('d.active', 1)
                    ->whereRaw('now() between begdate and ifnull(enddate,now())')
                    ->first();

                $userid = \Auth::user()->id;
                if (!isset($rec)) {
                    $rec = new orgdog([
                        "orgid" => $orgid,
                        "created_by" => $userid,
                        "created_at" => now(),
                        "updated_by" => $userid,
                        "updated_at" => now()
                    ]);
//            $mess = "Создана запись о договоре";
                }
                $rec->ownorgid = $ownorgid;
                $rec->updated_by = $userid;
                $rec->updated_at = now();
                $rec->save();
                return true;
            } else {
                $rec = orgdog::where('orgid', $orgid)
                    ->delete();
            }
        }
        return false;
    }

    public static function rqListOrg4Auto($request)
    {
        $search_str = $request->q;
        $itID = $request->it;
//        $s_isservice = $request->get("svc");
//        $s_isservice = $request->get("svc");

        $search_str = strtolower(preg_replace('[!|-|/| +]', ' ', $search_str));
        $search_str = preg_replace('| +|', ' ', $search_str);

        $sc = "1=1";
        $words = explode(" ", $search_str);
        if (count($words) > 0) {
            $srch_flds = "lcase(concat(o.name,' ',ifnull(o.inn,' '),' ',ifnull(o.kpp,' ')))";
            $sc .= ' and (';

            //ищем "как ввел пользователь"
            $sc .= ' (1=1';
            foreach ($words as $word) {
                $sc .= " and {$srch_flds} like '%{$word}%'";
            }
            $sc .= ')';

            //еще попробуем вариант с перекодировкой - если пользователь забыл переключить клавиатуру на русский язык
            $words = explode(" ", StringUtil::switcher_ru($search_str));
            $sc .= ' or (1=1';
            foreach ($words as $word) {
                $sc .= " and {$srch_flds} like '%{$word}%'";
            }
            $sc .= ')';

            $sc .= ')';
        }


        $rq = org::
        select("o.id", "o.inn", "o.kpp", "o.name")
            ->from('orgs as o')
            ->where('o.active', 1)
            ->whereRaw($sc)
            ->orderby('o.name')
            ->orderby('o.inn');
        return $rq;
    }

    public static function newOrgByExtID($extsysid, $orgextid, $orgname, $userid = null)
    {
        //перепроверим - вдруг уже есть такая организация:
        $orgid = objextid::objid_by_extsysid_extid($extsysid, 111, $orgextid);
        if (!isset($orgid)) {
            //$userid = \Auth::user()->id;

            try {
                DB::beginTransaction();

                //Добавить организацию
                $org = new org([
                    "name" => $orgname,
                    "active" => 0,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $org->save();
                $orgid = $org->id;

                //Добавить идентификатор организации во внешней системе $extsysid
                $ext = new objextid([
                    "extsysid" => $extsysid,
                    "sysobjid" => 111,
                    "objid" => $orgid,
                    "extid" => $orgextid,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $ext->save();

                DB::commit();

            } catch (\Exception $e) {
                //DB::rollback();
                //$this->log->fatalerror($e->getMessage());
                //var_dump($e->getTraceAsString());
                \Log::debug($e->getMessage());
                return $e->getMessage();
                //return null;
            } finally {
            }

        }
        return $orgid;
    }

    static public function lstBuyers()
    {
        //массив записей с организациями-продавцами (Владельцами)
        Cache::forget('lstBuyers');
        return Cache::remember('lstBuyers', now()->addMinutes(25)
            , function () {
                return org::from('orgs as o')
                    ->select('o.id', 'o.name')
                    ->where('o.active', 1)
                    ->orderby('o.name')
                    ->get()
                    ->pluck("name", "id");
            });
    }


    static public function lstActiveOrgsWithFlag($flagtypeid)
    {
        //массив записей с организациями - обладателями заданного флага $flagtypeid
        Cache::forget('lstActiveOrgsWithFlag_' . $flagtypeid);

        return Cache::remember('lstActiveOrgsWithFlag_' . $flagtypeid, now()->addMinutes(25)
            , function () use ($flagtypeid) {
                return org::from('orgs as o')
                    ->select('o.id', 'o.name')
                    ->where('o.active', 1)
                    ->whereExists(function ($query) use ($flagtypeid) {
                        $query->select(DB::raw(1))
                            ->from('objflags as f')
                            ->where('f.sysobjid', 111)
                            ->wherecolumn('f.objid', 'o.id')
                            ->where('f.flagtypeid', $flagtypeid);
                    })
                    ->orderby('o.name')
                    ->get()
                    ->pluck("name", "id")->toArray();
            });
    }

    static public function lstActiveOrgs()
    {
        //массив записей с активными организациями
        return Cache::remember('lstActiveOrgs', now()->addMinutes(25)
            , function () {
                return org::from('orgs as o')
                    ->select('o.id', 'o.name')
                    ->where('o.active', 1)
                    ->orderby('o.name')
                    ->get()
                    ->pluck("name", "id")->toArray();
            });
    }

    static public function lstOwnOrgsFromInvoices()
    {
        //массив записей с организациями (холдинга), присутствующими в invoices.ownorgid
        return Cache::remember('lstOwnOrgsFromInvoices', now()->addMinutes(15)
            , function () {
                return org::from('orgs as o')
                    ->whereRaw("exists (select 1 from invoices as inv where inv.ownorgid=o.id)")
                    ->where('o.active', 1)
                    ->select('o.id', 'o.name')
                    ->orderby('o.name')
                    ->get()
                    ->pluck("name", "id")->toArray();
            });
    }

    static public function lstOwnOrgsFromInvoicesForUser($userid)
    {
        //массив записей с организациями (холдинга), присутствующими в invoices.ownorgid
        //но пользователь должен иметь права представительства на эту организацию

        return Cache::remember('lstOwnOrgsFromInvoicesForUser_' . $userid, now()->addMinutes(15)
            , function () use ($userid) {
                return org::from('orgs as o')
                    ->whereRaw("exists (select 1 from invoices as inv where inv.ownorgid=o.id)")
                    ->whereRaw("exists (select 1 from userorgs as uo where uo.orgid=o.id
                        and uo.userid={$userid}
                        and uo.active=1 and now() between uo.begdt and ifnull(uo.enddt,now()) )")
                    ->where('o.active', 1)
                    ->select('o.id', 'o.name')
                    ->orderby('o.name')
                    ->get()
                    ->pluck("name", "id")->toArray();
            });
    }

    static public function lstOrgsFromInvoices()
    {
        //массив записей с контрагентами, присутствующими в invoices.orgid
        return Cache::remember('lstOrgsFromInvoices', now()->addMinutes(15)
            , function () {
                return org::from('orgs as o')
                    ->whereRaw("exists (select 1 from invoices as inv where inv.orgid=o.id)")
                    ->where('o.active', 1)
                    ->select('o.id', 'o.name')
                    ->orderby('o.name')
                    ->get()
                    ->pluck("name", "id")->toArray();
            });
    }

    static public function lstOrgsFromInvoicesForUser($userid)
    {
        //массив записей с контрагентами, присутствующими в invoices.orgid
        return Cache::remember('lstOrgsFromInvoices', now()->addMinutes(15)
            , function () use ($userid) {
                return org::from('orgs as o')
                    ->where('o.active', 1)
                    ->whereRaw("exists (select 1 from invoices as inv
                        where inv.orgid=o.id
                        and exists (select 1 from userorgs as uo where uo.orgid=inv.ownorgid
                            and uo.userid={$userid}
                            and uo.active=1 and now() between uo.begdt and ifnull(uo.enddt,now()) ))")
                    ->select('o.id', 'o.name')
                    ->orderby('o.name')
                    ->get()
                    ->pluck("name", "id")->toArray();
            });
    }

    static public function lstActiveOrgsWithStaff()
    {
        //массив записей с активными организациями имеющими сотрудников
        return Cache::remember('lstActiveOrgsWithStaff', now()->addMinutes(25)
            , function () {
                return org::from('orgs as o')
                    ->select('o.id', 'o.name')
                    ->where('o.active', 1)
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('orgstaff as os')
                            ->wherecolumn('os.orgid', 'o.id')
                            ->where('os.active', 1);
                    })
                    ->orderby('o.name')
                    ->get()
                    ->pluck("name", "id")->toArray();
            });
    }

    static public function lstCarrierOrgs()
    {
        //массив записей с организациями - обладателями заданного флага $flagtypeid
        Cache::forget('lstCarrierOrgs');
        return Cache::remember('lstCarrierOrgs', now()->addMinutes(25)
            , function () {
                $recs = org::from('orgs as o')
                    ->select('o.id', 'o.name')
                    ->selectraw('ifnull(( select 1 from objflags as f2 where f2.sysobjid=111 and f2.objid=o.id and f2.flagtypeid=12),0) as grp')
                    ->where('o.active', 1)
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('objflags as f')
                            ->where('f.sysobjid', 111)
                            ->wherecolumn('f.objid', 'o.id')
                            ->where('f.flagtypeid', 14);
                    })
                    ->orderby('grp', 'desc')
                    ->get();
                //->toArray();

                //Образец
                // $car_orgs = array(
                //     'Наши компании' => array(6=>'ООО УМТС', 7=>'Apple'),
                //     'Сторонние компании' => array(13=>'Chicken', 9=>'ИП АВАГЯН П.С.'),
                // );

                //приведем выборку к формату образца
                $grpnames = [0 => 'сторонние компании', 1 => 'свои компании'];
                $rslt = [];
                $curgrp = -1;
                foreach ($recs as $rec) {
                    if ($rec->grp <> $curgrp) {
                        if ($curgrp <> -1)
                            $rslt += [$grpnames[$curgrp] => $lst];

                        $curgrp = $rec->grp;
                        $lst = [];
                    }
                    $lst += [$rec->id => $rec->name];
                }
                if ($curgrp <> -1)
                    $rslt += [$grpnames[$curgrp] => $lst];

                return $rslt;
            });
    }


    static public function search_cond($params)
    {

        $userid = \Auth::user()->id;

        $sc = "1=1";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];
        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'equiprsts_worker') {
                        //организация присутствует как подрядчик в заявках на материалы
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists(select 1 from equiprqsts as er where er.orgid = o.id)";

                    } elseif ($key == 'active') {
                        $sc .= " and o.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (o.active=1 or o.id={$val})";

                    } elseif ($key == 'name' or $key == 's_name') {
                        $search_flds = "o.name";

                        $words = explode(" ", $val);
                        if (count($words) > 0) {
                            $sc .= ' and (';

                            //ищем "как ввел"
                            $sc .= ' (1=1';
                            foreach ($words as $word) {
                                $sc .= " and {$search_flds} like '%" . $word . "%'";
                            }
                            $sc .= ')';

                            //попробуем вариант с перекодировкой - если пользователь забыл переключить клавиатуру на русский язык
                            $words = explode(" ", StringUtil::switcher_ru($val));
                            $sc .= ' or (1=1';
                            foreach ($words as $word) {
                                $sc .= " and {$search_flds} like '%" . $word . "%'";
                            }
                            $sc .= ')';

                            $sc .= ')';
                        }
                    } elseif ($key == 'name_inn') {
                        $search_flds = "concat(o.name,' ',ifnull(o.inn,' '))";

                        $words = explode(" ", $val);
                        if (count($words) > 0) {
                            $sc .= ' and (';

                            //ищем "как ввел"
                            $sc .= ' (1=1';
                            foreach ($words as $word) {
                                $sc .= " and {$search_flds} like '%" . $word . "%'";
                            }
                            $sc .= ')';

                            //попробуем вариант с перекодировкой - если пользователь забыл переключить клавиатуру на русский язык
                            $words = explode(" ", StringUtil::switcher_ru($val));
                            $sc .= ' or (1=1';
                            foreach ($words as $word) {
                                $sc .= " and {$search_flds} like '%" . $word . "%'";
                            }
                            $sc .= ')';

                            $sc .= ')';
                        }

                    } elseif ($key == 'er_buildobjid') {
                        //организация связана с заявками на материалы для указанного объекта стрительства
                        // через счета - поставщик=o.id
                        $sc .= " and exists (select 1 from invoices as inv
                                    where inv.orgid=o.id
                                    )";

                    } elseif ($key == 'in_regnum_srcs') {
                        //организация имеет источник рег. номеров
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from regnum_srcs as rns where rns.ownorgid=o.id)";

                    } elseif ($key == 'in_orgstaff') {
                        //организация имеет персонал
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from orgstaff as os where os.orgid=o.id)";

                    } elseif ($key == 'in_machines') {
                        //организация владеет спецтехнику
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from machines as m where m.orgid=o.id)";

                    } elseif ($key == 'in_jts') {
                        //спецтехника использовалась в табеле
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from machines as m
                                    join jts_machines as jm on jm.machineid=m.id
                                    where m.orgid=o.id)";

                    } elseif ($key == 'in_wrkrep_machines') {
                        //организация-заказчик в отчетах о работе
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from wrkrep_machines as wrm
                                    where wrm.orgid=o.id)";

                    } elseif ($key == 'in_orgplnpays') {
                        // использовалась в плане платежей как плательщик
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from orgplnpays as pp
                                    where pp.ownorgid=o.id)";

                    } elseif ($key == 'in_orgplnpay_items') {
                        // использовалась в плане платежей как получатель
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from orgplnpay_items as pi
                                    where pi.orgid=o.id)";

                    } elseif ($key == 'in_userorgs') {
                        // пользователь представляет организацию
                        $sc .= " and exists (select 1 from userorgs as uo where uo.orgid=o.id
                                and uo.userid={$val} and uo.active=1
                                and now() between uo.begdt and ifnull(uo.enddt,now()) )";

                    } elseif ($key == 'in_doc_orgs') {
                        // организация связана с любым документом (через obj_orgs)
                        $sc .= " and " . (($val == 0) ? "not" : "") . " exists (select 1 from obj_orgs as ojo
                                where ojo.orgid=o.id
                                    and ojo.sysobjid=1701
                                    and ojo.active=1
                                 )";

                    } elseif ($key == 'in_stf_wrkhrs') {
                        // сотрудники указанной организации имеют записи в stf_wrkhrs
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from orgstaff as os where os.orgid=o.id
                                    and exists(select 1 from stf_wrkhrs as swh where swh.staffid=os.id)
                                        )";

                    } elseif ($key == 'in_stf_chrg_calcs') {
                        // сотрудники указанной организации имеют записи в in_stf_chrg_calcs
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from orgstaff as os where os.orgid=o.id
                                    and exists(select 1 from stf_chrg_calcs as scs where scs.staffid=os.id)
                                        )";

                    } elseif ($key == 'in_driver_works_ownorgid') {
                        // сотрудники указанной организации имеют записи в driver_works
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from orgstaff as os where os.orgid=o.id
                                    and exists(select 1 from driver_works as dw where dw.staffid=os.id)
                                        )";

                    } elseif ($key == 'in_budgets') {
                        // организация имеет бюджет
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from budgets as b where b.orgid=o.id)";

                    } elseif ($key == 'in_budgets_for_buildobjid') {
                        // организация имеет бюджет
                        $sc .= " and exists (select 1 from budgets as b where b.orgid=o.id and b.buildobjid={$val})";

                    } elseif ($key == 'in_userorgs_with_acs_orgplnpays') {
                        // пользователь представляет организацию

                        $sc .= " and exists (select 1 from userorgs as uo where uo.orgid=o.id
                                and uo.userid={$val} and uo.active=1 and uo.acs_orgplnpays=1
                                and now() between uo.begdt and ifnull(uo.enddt,now()) )";

                    } elseif ($key == 'in_org_charge') {
                        //для организации определены виды начислений/удержаний
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from org_charges as oc where oc.orgid=o.id)";

                    } elseif ($key == 'ownorg_in_bills') {
                        // использовалась в счетах как плательщик
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from invoices as inv
                                    where inv.ownorgid=o.id)";

                    } elseif ($key == 'org_in_bills') {
                        // использовалась в счетах как поставщик
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from invoices as inv
                                    where inv.orgid=o.id)";

                    } elseif ($key == 'in_bills_for_materials') {
                        // использовалась в счетах как получатель/поставщик
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from invoices as inv
                                    where inv.orgid=o.id and inv.categoryid=7)";

                    } elseif ($key == 'in_bills_for_cat12') {
                        // использовалась в счетах на категорию 12 (Материалы(срочно)) как получатель/поставщик
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from invoices as inv
                                    where inv.orgid=o.id and inv.categoryid=12)";

                    } elseif ($key == 'in_wrhdocs_ownorgid') {
                        // использовалась в документах складского учета как Компания-владелец
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from wrhdocs as d where d.ownorgid=o.id)";

                    } elseif ($key == 'in_documents_ownorg') {
                        // использовалась в архиве документов как Компания-регистратор (в чьей канцелярии)
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from documents as d  where d.ownorgid=o.id)";

                    } elseif ($key == 'in_documents_srcorg') {
                        // использовалась в архиве документов как Источник/Инициатор
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from documents as d  where d.src_orgid=o.id)";

                    } elseif ($key == 'user_has_right_for_org') {
                        // пользователь должен иметь указанное право на объекты, связанные с организацией

                        $sc .= " and exists( select 1 FROM usrsysrights AS usr
                                    where
                                        usr.userid = {$userid} and usr.sysfuncid = {$val}
                                                and usr.active = 1
                                                and now() between usr.begdt and IFNULL(usr.enddt, now())
                                                and IFNULL(usr.limsysobjid, 111) = 111
                                                and IFNULL(usr.limobjid, o.id) = o.id
                                        )";

                    } elseif ($key == 'orgplnpays_ownorgid_in') {
                        //orgplnpays.ownorgid должен быть в переданном списке
                        $sc .= " and exists (select 1 from orgplnpays as pp
                                    join orgplnpay_items as pi on pi.docid=pp.id
                                    where pi.orgid=o.id and pp.ownorgid in ({$val})
                                    )";

                    } elseif ($key == 'buildobj_sup_not_dlvrd') {
                        //список поставщиков на заданный объект имеющих долг по доставке материалов
                        $sc .= " and exists (select 1 from invoices as inv
                                    join eritm_offers as ofr on ofr.invoiceid=inv.id
                                    join equiprqst_items as eri on eri.id=ofr.eritmid
                                    join equiprqsts as er on er.id=eri.rqstid and er.buildobjid={$val}
                                    where o.id=inv.orgid )";

                    } elseif ($key == 'buildobj_ownorg_not_dlvrd') {
                        //список поставщиков на заданный объект имеющих долг по доставке материалов
                        $sc .= " and exists (select 1 from invoices as inv
                                    join eritm_offers as ofr on ofr.invoiceid=inv.id and ofr.ord_qty>ifnull(ofr.dlvrd_qty,0)
                                    join equiprqst_items as eri on eri.id=ofr.eritmid
                                    join equiprqsts as er on er.id=eri.rqstid and er.buildobjid={$val}
                                    where o.id=inv.ownorgid )";

                    } elseif ($key == 'org_ownorg_not_dlvrd') {
                        //список поставщиков на заданный объект имеющих долг по доставке материалов
                        $sc .= " and exists (select 1 from invoices as inv
                                    join eritm_offers as ofr on ofr.invoiceid=inv.id and ofr.ord_qty>ifnull(ofr.dlvrd_qty,0)
                                    where o.id=inv.ownorgid and inv.orgid={$val} )";

                    } elseif ($key == 'inv_ownorgid_by_orgid') {
                        //организация является получателем/плательщиком в счетах для заданного поставщика
                        $sc .= " and exists (select 1 from invoices as inv
                                    where o.id=inv.ownorgid
                                    and inv.doctypeid=1 and inv.orgid={$val} )";

                    } elseif ($key == 'upd_orgid_by_ownorgid') {

                        $sc .= " and exists (select 1 from invoices as inv
                                    where o.id=inv.orgid
                                    and inv.doctypeid=2 and inv.ownorgid={$val}";

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

                    } elseif ($key == 'inv_orgid_by_ownorgid') {

                        $sc .= " and exists (select 1 from invoices as inv
                                    where o.id=inv.orgid
                                    and inv.doctypeid=1 and inv.ownorgid={$val}";

                        if (isset($params['inv_begdate']) and array_search('inv_begdate', $used_params) == 0) {
                            $tdate = $params['inv_begdate'];
                            $sc .= " and inv.docdate>='{$tdate}'";
                            $used_params[] = 'inv_begdate';
                        }
                        if (isset($params['inv_enddate']) and array_search('inv_enddate', $used_params) == 0) {
                            $sc .= " and inv.docdate<='" . $params['inv_enddate'] . "'";
                            $used_params[] = 'inv_enddate';
                        }
                        $sc .= ")";

                    } elseif ($key == 'upd_orgid') {
                        //организация-поставщик по УПД
                        $sc .= " and exists (select 1 from invoices as inv
                                    where inv.doctypeid=2 and inv.orgid={$val})";

                    } elseif ($key == 'upd_begdate') {
                        //
                        $sc .= " and exists (select 1 from invoices as inv
                                where inv.doctypeid=2 and o.id in(inv.ownorgid,inv.orgid)
                                    and inv.docdate>='" . $params['upd_begdate'] . "'";

                        if (isset($params['upd_enddate']) and array_search('upd_enddate', $used_params) == 0) {
                            $sc .= " and inv.docdate<='" . $params['upd_enddate'] . "'";
                            $used_params[] = 'upd_enddate';
                        }
                        $sc .= ")";

                    } elseif ($key == 'upd_enddate') {
                        $sc .= " and exists (select 1 from invoices as inv
                                where inv.doctypeid=2 and o.id in(inv.ownorgid,inv.orgid)
                                    and inv.docdate<='" . $params['upd_enddate'] . "'";

                        if (isset($params['upd_begdate']) and array_search('upd_begdate', $used_params) == 0) {
                            $sc .= " and inv.docdate>='" . $params['upd_begdate'] . "'";
                            $used_params[] = 'upd_begdate';
                        }
                        $sc .= ")";


                    } elseif ($key == 'with_stocks') {
                        //организация имеет товарный запас на складе
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from wrh_stocks as ws
                                    where ws.ownorgid=o.id and ws.qty>0)";

                    } elseif ($key == 'in_wrhdocs_ownorg') {
                        // использовалась в документах склада как Компания-владелец склада
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from wrhdocs as d where d.ownorgid=o.id)";

                    } elseif ($key == 'in_wrhdocs_saleorgid') {
                        // использовалась в документах склада как Компания-продавец товара
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from wrhdocs as d where d.saleorgid=o.id)";

                    } elseif ($key == 'in_wrhdocs_org') {
                        // использовалась в документах склада как Компания-контрагент
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from wrhdocs as d  where d.orgid=o.id)";

                    } elseif ($key == 'in_mchn_raids_ownorgid') {
                        //организация указана в  mchn_raids.ownorgid
                        $sc .= " and " . (($val == 0) ? "not" : "")
//                            . " exists (select 1 from mchn_raids as mr where mr.load_ownorgid=o.id)";
                            //. " exists (select 1 from mchn_raids as mr where mr.suporgid=o.id)";
                            //2023-10-17
                            . " exists (select 1 from mr_opers as mro where mro.suporgid=o.id and sale_dir=1)";

                    } elseif ($key == 'in_mchn_raids_orgid') {
                        //организация указана в  mchn_raids.ownorgid
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from mchn_raids as mr where mr.orgid=o.id)";

                    } elseif ($key == 'in_mr_opers_with_suporgid') {
                        //организация указана в orgid в одной записи с указзаной suporgid (поставщик)
                        $sc .= " and exists (select 1 from mr_opers as mro where mro.orgid=o.id and mro.suporgid=$val)";

                    } elseif ($key == 'in_mr_opers_suporgid') {
                        //организация указана в mr_opers.suporgid (поставщик)
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from mr_opers as mro where mro.suporgid=o.id)";

                    } elseif ($key == 'in_mr_opers_orgid') {
                        //организация указана в  mr_opers.orgid
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from mr_opers as mro where mro.orgid=o.id)";

                    } elseif ($key == 'in_mr_opers_orgid_sale') {
                        //организация указана в  mr_opers.orgid
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from mr_opers as mro where mro.orgid=o.id and mro.sale_dir=1)";

                    } elseif ($key == 'in_mr_opers') {
                        //организация указана в mr_opers.orgid или в mr_opers.sup_orgid
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from mr_opers as mro where o.id in (mro.suporgid, mro.orgid))";

                    } elseif ($key == 'in_mr_opers_orgid_with_wrkdate_ge') {
                        //организация указана в mr_opers.orgid документе с датой >= $val
                        $sc .= " and exists (select 1 from mr_opers as mro
                                                join mchn_raids as mr on mr.id=mro.mr_id
                                                where mro.orgid = o.id
                                                  and mr.wrkdate >= '{$val}' )";

                    } elseif ($key == 'in_mr_opers_orgid_with_wrkdate_le') {
                        //организация указана в mr_opers.orgid документе с датой <= $val
                        $sc .= " and exists (select 1 from mr_opers as mro
                                                join mchn_raids as mr on mr.id=mro.mr_id
                                                where mro.orgid = o.id
                                                  and mr.wrkdate <= '{$val}' )";

                    } elseif ($key == 'in_mr_opers_with_wrkdate_ge') {
                        //организация указана в mr_opers.orgid или в mr_opers.sup_orgid документе с датой >= $val
                        $sc .= " and exists (select 1 from mr_opers as mro
                                                join mchn_raids as mr on mr.id=mro.mr_id
                                                where o.id in (mro.suporgid, mro.orgid)
                                                  and mr.wrkdate >= '{$val}' )";

                    } elseif ($key == 'in_mr_opers_with_wrkdate_le') {
                        //организация указана в mr_opers.orgid или в mr_opers.sup_orgid документе с датой <= $val
                        $sc .= " and exists (select 1 from mr_opers as mro
                                                join mchn_raids as mr on mr.id=mro.mr_id
                                                where o.id in (mro.suporgid, mro.orgid)
                                                  and mr.wrkdate <= '{$val}' )";

                    } elseif ($key == 'flagtypeid') {
                        //у организации должен быть нужный признак
                        $sc .= " and exists (select 1 from objflags as f
                                        where f.objid=o.id
                                        and f.sysobjid=111
                                        and f.flagtypeid={$val}
                                    )";

                    } elseif ($key == 'not_flagtypeid') {
                        //у организации не должно быть заданного признака
                        $sc .= " and not exists (select 1 from objflags as f
                                        where f.objid=o.id
                                        and f.sysobjid=111
                                        and f.flagtypeid={$val}
                                    )";

                    } elseif ($key == 'flagtypeid_or_id') {
                        //у организации должен быть нужный признак или id организации задан явно
                        $flagtypeid = $val[0] ?? null;
                        $sc .= " and ( exists (select 1 from objflags as f
                                        where f.objid=o.id
                                        and f.sysobjid=111
                                        and f.flagtypeid={$flagtypeid}
                                    )";

                        if (isset($val[1])) {
                            $orgid = $val[1] ?? null;
                            $sc .= " or o.id={$orgid}";
                        }
                        $sc .= ")";

                    } elseif ($key == 'in_paydocs_ownorgid') {
                        // использовалась в платежных документах в собственной компании
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from paydocs as pd where pd.ownorgid=o.id)";

                    } elseif ($key == 'in_paydocs_orgid') {
                        // использовалась в платежных документах в контрагенте
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from paydocs as pd where pd.orgid=o.id)";

                    } elseif ($key == 's_in_contract_type') {
                        // использовалась в платежных документах в контрагенте
                        $sc .= " and exists (select 1 from contract_orgs as co
                            join contracts as c on c.id=co.contractid and c.contracttypeid={$val}
                        where co.orgid=o.id)";

                    } elseif ($key == 's_addresstypeid') {
                        // Контрагент имеет адрес указанного типа
                        $sc .= " and exists (select 1 from obj_addresses as oa
                            where oa.sysobjid=111 and oa.objid=o.id and  oa.addresstypeid={$val})";

                    } elseif ($key == 'in_ri_sup_prices') {
                        //организация указана в ri_sup_prices.orgid
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from ri_sup_prices as rsp where rsp.orgid=o.id)";

                    } elseif ($key == 'in_fuelcards') {
                        //организация указана в fuelcards
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from fuelcards as fc where fc.orgid=o.id)";

                    } elseif ($key == 'in_fuelcards_suporgid') {
                        //организация указана в fuelcards как поставщик
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from fuelcards as fc where fc.suporgid=o.id)";
                    }

                }

            }
        }
        //Log::info($sc);

        return $sc;

    }


    static public function lstFor($params)
    {
        //2021-02-25 SNS. универсальный конструктор массива с id, name организаций
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $userid = \Auth::user()->id;

            //для оптимизации запроса некоторые параметры обрабатываются группой.
            // Чтобы избежать повторного применения, используем добавление отработанных параметров
            // в массив $used_params
            $used_params = [];

            $sc = self::search_cond($params);
            //Log::info($sc);

            $lst = self::from('orgs as o')
                ->whereRaw($sc)
                ->select('id', 'name')
                ->orderBy('o.name', 'desc')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-07-10 SNS. кэшируемый результат списка

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

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей orgs
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'o.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['o.name', 'asc']];

            $recs = self::from('orgs as o')
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


    static public function list_for_ac(Request $request)
    {
        //2021-10-19 SNS. Для автокомплита

        $result = "";
        try {

            $list = self::getFor([
                's_name' => $request->s_name,
                'name_inn' => $request->name_inn,
                'flagtypeid' => $request->flagtypeid,
                'active' => $request->active,
            ],
                ['o.id', 'o.name', 'o.inn']);

            $result = $list;

        } catch (\Exception $e) {
            Log::error('org::list_for_ac:' . $e->getMessage());
        }
        return response()->json($result);
    }


    static public function getBoss_UserID($orgid)
    {
        //возвращает ID пользователя, соответствующего руководителю заданной организации
        if (!isset($orgid))
            return null;

        $boss_staffid = self::find($orgid)->boss_staffid ?? null;
        if (!isset($boss_staffid))
            return null;

        return orgstaff::find($boss_staffid)->userid ?? null;

    }

    static public function lstSaldos_cached($orgid)
    {
        //массив записей с организациями-контрагентами
        // рассчитываем сумму полученных товаров/услуг по сумме УПД,
        //  а сумму оплат - по оплате счетов
        return Cache::remember('lstInvSaldos_' . $orgid, now()->addMinutes(15)
            , function () use ($orgid) {
                return invoice::from('invoices as inv')
                    ->join('orgs as oo', 'oo.id', 'inv.ownorgid')
                    ->where('inv.orgid', $orgid)
                    ->selectRaw("ownorgid, max(oo.name) as ownorg_name, sum(docsum) as inv_sum
 , sum(ifnull((select sum(docsum) from invoices as upd where upd.doctypeid=2 and upd.pardocid=inv.id),0)) as upd_sum
 , sum(ifnull((select sum(fctpaysum) from orgplnpay_items as ppi where ppi.src_sysobjid=915 and ppi.src_objid=inv.id),0)) as pay_sum")
                    ->groupBy('inv.ownorgid')
                    ->get();;
            });
    }


    public static function import_honest_business($orgid)
    {
        //Получение данных о компании с сайта https://zachestnyibiznes.ru/
        //
//        $str = "123&nbsp;? 5678";
//        $str = str_replace('&nbsp;', ' ', $str);
//        $str = str_replace(' ? ', ' ', $str);
//        dd($str);

        if (!isset($orgid))
            return ['error' => 'Не задана организация!'];

        $org = self::find($orgid);
        if (!isset($org) or !isset($org->inn))
            return ['error' => 'Не задан ИНН!'];

        $userid = \Auth::user()->id;

        $s_inn = $org->inn;

//        $s_inn = '2540228469';  //"Специализированный застройщик "ИНДУСТРИЯ", ООО
//        $s_inn = '253900677221';  //ИП Бавыкин
//        $s_inn = '2510015791 ';  //ВостокСпецТех
//        $s_inn = '2543141576';  //Баско-Консалтиннг

        $lk_url = 'https://zachestnyibiznes.ru/';

        $result = new Result();
        $client = new Client();

        try {

            $crawler = $client->request('GET', $lk_url);
            //$html = $client->getResponse()->getContent();

            $form = $crawler->selectButton('Найти')->form();
            //dd($crawler, $html, $form);

            $crawler = $client->submit($form, array('query' => $s_inn));

//        $crawler->filter('.errors')->each(function ($node) {
//            var_dump($node->text());
//        });

            $del_str = $crawler->filter('.helloparsers')->each(function ($node, $i) {
                    //var_dump($node->text());
                    return $node->text();
                })[0] ?? '';

// 2021-09-27 Новый формат
//        $org_fullname = $crawler->filter('a[itemprop*="legalName"]')->each(function ($node, $i) {
//                return $node->html();
//            })[0] ?? '';
//        dd($org_fullname,$crawler);

            $org_fullname = $crawler->filter('.rwd-table tr td a[itemprop*="legalName"] span')->each(function ($node, $i) {
//            var_dump($node->html());
//            print "<hr>";
//            var_dump($node->text());

                    return $node->html();
                })[0] ?? '';


            $org_name = $crawler->filter('.rwd-table tr td a[itemprop*="legalName"]')
                    ->each(function ($node, $i) use ($org_fullname) {
                        $val = str_replace($org_fullname, '', $node->text());

                        return $val;
                    })[0] ?? '';

            $founder = $crawler->filter('.rwd-table td[itemprop*="founder"]')->each(function ($node, $i) {
                    //var_dump($node->text());
                    return $node->text();
                })[0] ?? '';
            $foundingDate = $crawler->filter('.rwd-table td[itemprop="foundingDate"]')->each(function ($node, $i) {
                    //var_dump($node->text());
                    return $node->text();
                })[0] ?? '';
            $address = $crawler->filter('.rwd-table td[itemprop="address"]')->each(function ($node, $i) {
                    //var_dump($node->text());
                    return str_replace('По данным портала ЗАЧЕСТНЫЙБИЗНЕС', '', $node->text());
                })[0] ?? '';


            //переход на страницу с доп. сведениями -----------------------------------------
            $crawler = $client->click($crawler->selectLink('смотреть')->link());


            $rating = $crawler->filter('.box-rating')->each(function ($node, $i) {
                    //var_dump($node->text());
                    return $node->text();
                })[0] ?? '';

            $ogrn = $crawler->filter('#ogrn')->each(function ($node, $i) {
                    //var_dump($node->text());
                    return $node->text();
                })[0] ?? '';

            $okpo = $crawler->filter('#okpo')->each(function ($node, $i) {
                    //var_dump($node->text());
                    return $node->text();
                })[0] ?? '';


            $boss_postname = $crawler->filter('div[itemtype="http://schema.org/OrganizationRole"]')->each(function ($node, $i) {
                    $words = explode('По данным портала ЗАЧЕСТНЫЙБИЗНЕС', $node->text());
                    return trim($words[2]);
                })[0] ?? '';

            $main_activity = $crawler->filter('div[itemprop="isicV4"]')->each(function ($node, $i) use ($del_str) {
                    //dd($node->text());
                    $val = str_replace('&nbsp;', ' ', $node->text());
                    $val = str_replace(' ? ', ' ', $val);
                    $val = str_replace('Основной вид деятельности:', '', $val);
                    $val = str_replace($del_str, '', $val);
                    $val = mb_substr($val, 3);
                    //var_dump($val);
                    return $val;
                })[0] ?? '';

            $org->fullname = $org_fullname;
            $org->boss_fullname = $founder;
            $org->boss_postname = $boss_postname;
            $org->ogrn = $ogrn;
            $org->okpo = $okpo;
            $org->address = $address;
            $org->main_activity = mb_substr($main_activity, 0, 300);
            $org->notes = 'Рейтинг: ' . $rating . ' (по данным портала zachestnyibiznes.ru)';

            $org->updated_by = $userid;
            $org->updated_at = now();

            //соберем строку с измененными полями -------------------------------------------------------------------
            $diffs = self::field_diff_list($org, ['id', 'created_by', 'updated_by', 'created_at', 'updated_at']);
            if ($diffs === '')
                $rslt_msg = "Запись пересохранена без изменений";
            else {
                $rslt_msg = 'Запись изменена: ' . $diffs;
            }
            //-------------------------------------------------------------------------------------------------------

            $org->save();

            objlog::log_info(self::$sysobjid, $org->id, $rslt_msg, 5);


            //dd(333, $crawler, $org_name, $founder, $foundingDate, $address, $rating[0], $okpo, $main_activity[0]);
            //dd(333, $boss_postname, $org_name, $founder, $foundingDate, $address, $rating[0], $okpo, $main_activity[0]);

            objlog::log_info(self::$sysobjid, $org->id, 'Данные об организации взяты с портала zachestnyibiznes.ru');

            $result->err = 0;
            $result->msg = 'Данные об организации взяты с портала zachestnyibiznes.ru!';

        } catch (\Exception $e) {
            Log::debug($e->getMessage());
            return ['error' => 'Ошибка получения данных: ' . $e->getMessage()];

        } finally {
        }

        return ['success' => 'Данные об организации взяты с портала zachestnyibiznes.ru!'];
    }


    public static function impfrm_egrul($orgid)
    {
        //Получение данных о компании с сайта https://egrul.nalog.ru/index.html
        //
//        $str = "123&nbsp;? 5678";
//        $str = str_replace('&nbsp;', ' ', $str);
//        $str = str_replace(' ? ', ' ', $str);
//        dd($str);

        if (!isset($orgid))
            return ['error' => 'Не задана организация!'];

        $org = self::find($orgid);
        if (!isset($org) or !isset($org->inn))
            return ['error' => 'Не задан ИНН!'];

        $userid = \Auth::user()->id;

        $s_inn = $org->inn;


//        $s_inn = '2540228469';  //"Специализированный застройщик "ИНДУСТРИЯ", ООО
//        $s_inn = '253900677221';  //ИП Бавыкин
//        $s_inn = '2510015791 ';  //ВостокСпецТех
//        $s_inn = '2543141576';  //Баско-Консалтиннг

        $lk_url = 'https://egrul.nalog.ru/index.html';

        $result = new Result();
        $client = new Client();

        //try {

        $crawler = $client->request('GET', $lk_url);
        $html = $client->getResponse()->getContent();

        $form = $crawler->selectButton('Найти')->form();
        dd($crawler, $html, $form);
        //dd($crawler,  $form);

        $form->setValues([
            'query' => $s_inn,
        ]);

// gets back an array of values - in the "flat" array like above
        $values = $form->getValues();

// returns the values like PHP would see them,
// where "registration" is its own array
        $values2 = $form->getPhpValues();
        //dd($values, $values2);

        $crawler = $client->submit($form, array('query' => $s_inn));

        $crawler->filter('div .res-line')->each(function ($node) {
            var_dump($node->text());
        });
        dd($crawler);


//            $del_str = $crawler->filter('.helloparsers')->each(function ($node, $i) {
//                    //var_dump($node->text());
//                    return $node->text();
//                })[0] ?? '';


//        } catch (\Exception $e) {
//            Log::debug($e->getMessage());
//            //return $e->getMessage();
//
//        } finally {
//        }
    }

    public static function import_001($file, $rec)
    {
        //Импорт списка организаций из xlsx-файла в формате ___

        $userid = \Auth::user()->id;
        $result = new Result();

        $array = Excel::toArray(new invoiceImport, $file);
        $array = $array[0];
        //dd($array);

        //Названия полей ожидаем в первой строке
        $fields = $array[0];
        if (!(
            in_array('name', $fields)
            and in_array('inn', $fields)
        )) {
            $result->err = 1;
            $result->msg = 'Файл должен содержать колонки "name", "inn"!';
            $rec->result = $result;
            return $rec;
        }

        //перевернем колонки
        $fld_idx = array_flip($fields);

        $items_add_cnt = 0; //кол-во новых записей
        $items_upd_cnt = 0; //кол-во обновленных записей

        for ($i = 1; $i < count($array); $i++) {

            $name = $array[$i][$fld_idx['name']];
            $inn = $array[$i][$fld_idx['inn']];
            $kpp = (isset($fld_idx['kpp'])) ? $array[$i][$fld_idx['kpp']] : null;

            if (isset($name) and isset($inn)) {

                if (isset($kpp))
                    //Ключем считаем ИНН+КПП
                    $org = self::where([
                        'inn' => $inn,
                        'kpp' => $kpp,
                    ])->first();
                else
                    //Ключем считаем ИНН
                    $org = self::where(['inn' => $inn,])->first();

                if (!isset($org)) {

                    $org = new self([
                        'inn' => $inn,
                        'kpp' => $kpp,
                    ]);
                    ++$items_add_cnt;
                } else
                    ++$items_upd_cnt;

                $org->name = $array[$i][$fld_idx['name'] ?? ''] ?? '';
                //необязательно-присутствующие поля. Обновляем только при наличии - чтобы не затереть предыдущее значение
                if (isset($fld_idx['address']))
                    $org->address = $array[$i][$fld_idx['address']];
                //dd($org);
                $org->save();

                //обработаем телефоны
                if (isset($fld_idx['phone'])) {
                    $phones = $array[$i][$fld_idx['phone']] ?? null;
                    if (isset($phones)) {
                        $phones = explode(',', $phones);
                        if (is_array($phones) and count($phones) > 0) {
                            foreach ($phones as $phone) {
                                $phone = trim($phone);
                                obj_contact::addOrUpdate([
                                    'sysobjid' => self::$sysobjid,
                                    'objid' => $org->id,
                                    'contacttypeid' => 1,
                                    'contact' => $phone,
                                ], [
                                    'sysobjid' => self::$sysobjid,
                                    'objid' => $org->id,
                                    'contacttypeid' => 1,
                                    'contact' => $phone,
                                ]);
                            }
                        }
                    }
                }

                //обработаем адреса электронной почты
                if (isset($fld_idx['email'])) {
                    $emails = $array[$i][$fld_idx['email']] ?? null;
                    if (isset($emails)) {
                        $emails = explode(',', $emails);
                        if (is_array($emails) and count($emails) > 0) {
                            foreach ($emails as $email) {
                                $email = trim($email);
                                obj_contact::addOrUpdate([
                                    'sysobjid' => self::$sysobjid,
                                    'objid' => $org->id,
                                    'contacttypeid' => 2,
                                    'contact' => $email,
                                ], [
                                    'sysobjid' => self::$sysobjid,
                                    'objid' => $org->id,
                                    'contacttypeid' => 2,
                                    'contact' => $email,
                                ]);
                            }
                        }
                    }
                }

                //Обработаем Признаки
                if (isset($fld_idx['flags'])) {
                    $flags = $array[$i][$fld_idx['flags']] ?? null;
                    if (isset($flags)) {
                        $flags = explode(',', $flags);
                        if (is_array($flags) and count($flags) > 0) {
                            foreach ($flags as $flag) {
                                objflag::AddObjFlag(self::$sysobjid, $org->id, $flag);
                            }
                        }
                    }
                }

            }
        }

        $result->msg .= "- добавлено записей: {$items_add_cnt}" . PHP_EOL;
        $result->msg .= "- изменено записей: {$items_upd_cnt}" . PHP_EOL;

        $rec->result = $result;
        //--------------------------------------------------------------------------

        return $rec;
    }

}
