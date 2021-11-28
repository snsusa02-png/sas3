<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use DB;

class meeting_staff extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'meeting_staffs';
    static public $sysobjid = 867;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function meeting()
    {
        return $this->hasOne(meeting::class, 'id', 'protid');
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function orgstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid')
            ->withDefault();
    }

    static public function lstAllForMeeting($protid)
    {
        if (isset($protid)) {
            $lst = self::from('meeting_staffs as ms')
                ->leftJoin('orgstaff as os', function ($j) {
                    $j->on('os.id', 'ms.staffid');
                })
                ->leftJoin('orgs as o', function ($j) {
                    $j->on('o.id', 'ms.orgid');
                })
                ->leftJoin('orgs as o2', function ($j) {
                    $j->on('o2.id', 'os.orgid');
                })
                ->where('protid', $protid)
                ->select('ms.*'
                    , DB::raw('concat(os.lname," ",os.fname," ",os.mname) as staffname')
                    , 'o.name as orgname', 'o.phone as orgphone'
                    , 'os.postname', 'os.phone', 'os.email', 'os.userid'
                    , 'o2.name as stafforgname')
                //->orderby(DB::raw('ifnull(bs.ordr,99999)'))
                ->orderBy('o.name')
                ->orderBy('staffname')
                //->orderby(DB::raw('ifnull(os.ordr,99999)'))
                ->get();

            return $lst;
        } else
            return null;
    }


    static public function lstSignersForMeeting($mtngid)
    {
        if (isset($mtngid)) {
            $lst = self::from('meeting_staffs as bs')
                ->leftJoin('orgstaff as os', function ($j) {
                    $j->on('os.id', 'bs.staffid');
                })
                ->leftJoin('orgs as o', function ($j) {
                    $j->on('o.id', 'bs.orgid');
                })
                ->leftJoin('orgs as o2', function ($j) {
                    $j->on('o2.id', 'os.orgid');
                })
                ->where('protid', $mtngid)
                ->where('signright', 1)
                ->select('bs.id', 'bs.rolename', 'bs.staffid', 'bs.orgid'
                    , DB::raw('concat(os.lname," ",os.fname," ",os.mname) as staffname')
                    , DB::raw('concat(os.lname," ", substr(os.fname,1,1),".", substr(os.mname,1,1),".") as shortstaffname')
                    , 'bs.active', 'bs.ordr', 'bs.readed_at'
                    , 'o.name as orgname', 'o.phone as orgphone'
                    , 'os.postname', 'os.phone', 'os.email', 'os.userid'
                    , 'o2.name as stafforgname')
                ->orderBy('o.name')
                ->orderBy('staffname')
                ->get();

            return $lst;
        } else
            return null;
    }

    static public function userInStaff($userid, $mtngid)
    {
        // возвращает 1, если $userid входит в состав участников собрания $mtngid
        // иначе - 0

        $cnt = self::from('meeting_staffs as ms')
            ->join('orgstaff as os', function ($j) {
                $j->on('os.id', 'ms.staffid');
            })
            ->where('ms.protid', $mtngid)
            ->where('os.userid', $userid)
            ->count();
        return ($cnt > 0) ? 1 : 0;
    }

    static public function userMeetingStaffID($userid, $meetingid)
    {
        //Узнаем staffid пользователя $userid(если он входит в состав участников совещания $meetingid)
        if (isset($userid) and isset($meetingid))
            return meeting_staff::from('meeting_staffs as ms')
                    ->join('orgstaff as os', 'os.id', 'ms.staffid')
                    ->where('ms.protid', $meetingid)
                    ->where('os.userid', $userid)
                    ->select('ms.staffid')->first()
                    ->staffid ?? null;
        else
            return null;
    }

}
