<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\mchntype;
use App\Traits\Result;
use DB;

class mchnrqst extends Model
{
    static public $prefix = 'mchnrqsts';
    static public $sysobjid = 483;

    use DeleteTrait;

    protected $guarded = [];


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function inituser()
    {
        return $this->hasOne(User::class, 'id', 'inituserid')->withDefault();
    }

    public function rqstmchntype()
    {
        return $this->hasOne(mchntype::class, 'id', 'rqstmchntypeid')
            ->withDefault();
    }

    public function rqstmachine()
    {
        return $this->hasOne(machine::class, 'id', 'rqstmachineid')
            ->withDefault();
    }

    public function asgnmachine()
    {
        return $this->hasOne(machine::class, 'id', 'asgnmachineid')
            ->withDefault();
    }

    public function mchntype()
    {
        return $this->hasOne(mchntype::class, 'id', 'rqstmchntypeid')
            ->withDefault();
    }

    public function mchnrqsttype()
    {
        return $this->hasOne(mchnrqsttype::class, 'id', 'rqsttypeid')
            ->withDefault();
    }

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid')
            ->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid')
            ->withDefault();
    }

    public function src_org()
    {
        return $this->hasOne(org::class, 'id', 'src_orgid')
            ->withDefault();
    }

    public function tgt_org()
    {
        return $this->hasOne(org::class, 'id', 'tgt_orgid')
            ->withDefault();
    }

    public function car_org()
    {
        return $this->hasOne(org::class, 'id', 'car_orgid')
            ->withDefault();
    }

    public function driver_org()
    {
        return $this->hasOne(org::class, 'id', 'driver_orgid')
            ->withDefault();
    }

    public function buildobj()
    {
        return $this->hasOne(buildobj::class, 'id', 'buildobjid')
            ->withDefault();
    }

    public function buildopertype()
    {
        return $this->hasOne(buildopertype::class, 'id', 'buildopertypeid')
            ->withDefault();
    }

    public function wrkorg()
    {
        return $this->hasOne(org::class, 'id', 'wrkorgid')
            ->withDefault();
    }


    public function wrkcontract()
    {
        return $this->hasOne(contract::class, 'id', 'wrkcontractid')
            ->withDefault();
    }

    public function dcsn_user()
    {
        return $this->hasOne(User::class, 'id', 'dcsn_userid')->withDefault();
    }

    public function fct_user()
    {
        return $this->hasOne(User::class, 'id', 'fct_userid')->withDefault();
    }


    static public function machinesInRqsts()
    {
        //Cache::forget(self::$prefix . '_lstTypes');
        $data = Cache::remember(self::$prefix . '_machinesInRqsts', now()->addMinutes(25)
            , function () {
                $lst = machine::from('machines as m')
                    ->select('id', 'name')
                    ->whereraw('exists (select 1 from mchnrqsts as mr where mr.rqstmachineid=m.id)')
                    ->orderBy('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function machinesInAssgn()
    {
        //Cache::forget(self::$prefix . '_lstTypes');
        $data = Cache::remember(self::$prefix . '_machinesInRqsts', now()->addMinutes(25)
            , function () {
                $lst = machine::from('machines as m')
                    ->select('id', 'name')
                    ->whereraw('exists (select 1 from mchnrqsts as mr where mr.asgnmachineid=m.id)')
                    ->orderBy('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function orgsInRqsts()
    {
        //Cache::forget(self::$prefix . '_lstTypes');
        $data = Cache::remember(self::$prefix . '_orgsInRqsts', now()->addMinutes(25)
            , function () {
                $lst = org::from('orgs as o')
                    ->select('id', 'name')
                    ->whereraw('exists (select 1 from mchnrqsts as mr where mr.orgid=o.id)')
                    ->orderBy('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function statuses()
    {
        return [0 => 'черновик', 1 => 'ожидает решения', 2 => 'согласована', 3 => 'отклонена', 9 => 'завершена'
        ,'1,2'=>'для диспетчера'];
    }

    public function admindelete()
    {
        $result = new Result;
        //Удаляем себя вместе с дочками
        try {
            DB::transaction(function () {
//                $this->orgprices()->delete();
//                $this->images()->delete();
//                $this->extids()->delete();
//                $this->specinfos()->delete();
//                $this->altnames()->delete();
                return parent::delete();
            });
        } catch (\Exception $e) {
            $result->err = $e->errorInfo[0];
            $result->msg = 'Ошибка удаления записи: ' . $e->message;
        }
        return $result;
    }


    static public function initUsers()
    {
        //Cache::forget(self::$prefix . '_lstTypes');
        $data = Cache::remember(self::$prefix . 'initUsers', now()->addMinutes(5)
            , function () {
                $lst = org::from('users as u')
                    ->select('id', 'name')
                    ->whereraw('exists (select 1 from mchnrqsts as mr where mr.inituserid=u.id)')
                    ->orderBy('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }



    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
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
}
