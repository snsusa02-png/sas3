<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\Result;
use Illuminate\Database\Eloquent\Model;
use Cache;

class meeting extends Model
{
    static public $prefix = 'meetings';
    static public $sysobjid = 856;

    use DeleteTrait;
    use FilesTrait;

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

    public function stage()
    {
        return $this->hasOne(meet_stage::class, 'id', 'stageid')->withDefault();
    }

    public function items()
    {
        return $this->hasMany(meeting_item::class, 'protid', 'id')
            ->orderBy('ordr')
            ->with('exeorg');
    }

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid')->withDefault();
    }

    public function project()
    {
        return $this->hasOne(project::class, 'id', 'projid')->withDefault();
    }

    public function buildobj()
    {
        return $this->hasOne(buildobj::class, 'id', 'buildobjid')->withDefault();
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }

    //


    static public function lstAllForBuildObj($buildobjid)
    {
        if (isset($buildobjid)) {
            $lst = self::from('meetings as p')
                ->leftJoin('orgs as oo', function ($j) {
                    $j->on('oo.id', 'p.ownorgid');
                })
                ->where('buildobjid', $buildobjid)
                ->select('p.id', 'p.name', 'p.docnum', 'p.meet_begdt', 'p.meet_place', 'p.active'
                    , 'oo.name as ownorgname'
                )
                ->orderBy('p.meet_begdt', 'desc')
                ->get();

            return $lst;
        } else
            return null;
    }


    public static function Move2Stage($meetingid, $stageid)
    {
        //перевод заказа $ordid на указаный этап

        $rslt = new Result;
        $userid = \Auth::User()->id;

        $meeting = meeting::find($meetingid);
        if (isset($meeting)) {

            //проверим полномочия
            if ($meeting->stageid == 1) {
                //пользователь должен быть Инициатором
                if ($meeting->inituserid != $userid) {
                    $rslt->err = 1;
                    $rslt->msg = 'Вы не являетесь инициатором!';
                    return $rslt;
                }
            } else {
                //Пользователь должен быть текущим исполнителем
//                if ($meeting->workuserid != $userid) {
                if ($meeting->inituserid != $userid) {
                    $rslt->err = 1;
                    $rslt->msg = 'Вы не являетесь инициатором!';
                    return $rslt;
                }
            }

            //переводить можно либо на шаг вперед, либо на шаг назад
            //не обязательно id этапов должны быть последовательны (1,2,3,4,5),
            //но пока упрощенно считаем, что это именно так

            //dd($meeting->stageid, $stageid, abs($meeting->stageid - $stageid));

            if (abs($meeting->stageid - $stageid) > 1) {
                $rslt->err = 1;
                $rslt->msg = 'Нельзя пропускать этапы!';
                return $rslt;
            }

            // Проверки для кажого этапа
            if ($meeting->stageid == 1 and $stageid == 2) {
                //перевод с этапа черновик на этап согласования
                // ! Должен быть состав !

                if ($meeting->items->count() == 0) {
                    $rslt->err = 1;
                    $rslt->msg = 'Должна быть определена Повестка совещания и участники!';
                    return $rslt;
                }

                //убрать ознакомление и решение участников
                $cnt = meeting_staff::from('meeting_staffs as ms')
                    ->where('ms.protid', $meeting->id)
                    ->update(['readed_at' => null
                        , 'dcsn_at' => null
                        , 'time_accepted' => null
                        , 'dcsn_descript' => null
                        , 'free_begdt' => null
                        , 'free_enddt' => null
                    ]);

                // для совместимости, установим активность
                $meeting->active = 1;

                //Создадим/обновим событие в календаре
                meeting::make_event($meeting->id);
            }
            if ($meeting->stageid == 2 and $stageid == 1) {
                //Возврат с этапа согласования в черновик

                // для совместимости, уберем активность
                $meeting->active = 0;

                //заблокируем событие в календаре
                meeting::disable_event($meeting->id);

            } elseif ($meeting->stageid == 2 and $stageid == 3) {
                //перевод с этапа согласования на этап готовности к проведению
                // ! Каждый участник должен подтвердить свое участие или неучастие в совещании!

                //Проверяем подтвержденность всех участников только ДО начала собрания
                if ($meeting->meet_enddt > now()) {
                    //подсчитаем кол-во записей состава у которых нет согласованного количества
                    //учитываем только пользователей, так как только им мы можем отправить уведомление и дать интерфейс подтверждения участия
                    $cnt = meeting_staff::from('meeting_staffs as ms')
                        ->join('orgstaff as os', function ($join) {
                            $join->on('os.id', '=', 'ms.staffid');
                        })
                        ->join('users as u', 'u.id', 'os.userid')
                        ->where('ms.protid', $meeting->id)
                        ->whereraw('ifnull(ms.time_accepted,0)=0')
                        ->count();

                    if ($cnt > 0) {
                        $rslt->err = 1;
                        $rslt->msg = 'Все участники (пользователи ИС) должны подтвердить свое участие в совещании!';
                        return $rslt;
                    }
                }
            } elseif ($meeting->stageid == 4 and $stageid == 5) {
                //перевод с этапа "Проведение собрания" на этап "Контроль принятых решений"

                //не должно быть вопросов без принятых решений
                $cnt = meeting_item::from('meeting_items as mi')
                    ->where('mi.protid', $meeting->id)
                    ->where('mi.accepted', 1)
                    ->whereNull('mi.decision')
                    ->count();

                if ($cnt > 0) {
                    $rslt->err = 1;
                    $rslt->msg = 'По всем вопросам должно быть зарегистрировано решение!';
                    return $rslt;
                }

            } elseif ($meeting->stageid == 5 and $stageid == 6) {
                //перевод с этапа "Контроль принятых решений" на этап "Завершено/Отработано"

                //не должно быть вопросов без принятых отчетов об исполнении
                $cnt = meeting_item::from('meeting_items as mi')
                    ->where('mi.protid', $meeting->id)
                    ->where('mi.accepted', 1)
                    ->whereNull('mi.completed')
                    ->count();

                if ($cnt > 0) {
                    $rslt->err = 1;
                    $rslt->msg = 'По всем вопросам должно быть зарегистрирован отчет об исполнении!';
                    return $rslt;
                }

            }

            //видимо претензий нет - изменим этап и уведомим соответствующих пользователей
            $pre_stageid = $meeting->stageid;
            $pre_stagename = $meeting->stage->name;

            $meeting->stageid = $stageid;
            $meeting->save();

            $meeting = meeting::find($meetingid);
            $stagename = $meeting->stage->name;

            $rslt->msg = "Совещание c этапа '$pre_stageid: $pre_stagename' переведено на этап '$meeting->stageid: $stagename'";
            objlog::log_info(856, $meeting->id, $rslt->msg);

            Cache::forget('meet_stages_lstUsed');

        } else {
            $rslt->err = 1;
            $rslt->msg = 'Совещание не найдено!';
            return $rslt;
        }
        return $rslt;

    }

    static public function make_event($meeting_id)
    {
        //создаем связанное событие (events)

        $rslt = new Result;

        $meeting = meeting::find($meeting_id);
        if (isset($meeting)) {

            // попробуем найти связанное событие:
            $event = event::where(['src_sysobjid' => self::$sysobjid
                , 'src_objid' => $meeting_id])->first();

            if (!isset($event)) {
                $event = new event([
                    'src_sysobjid' => self::$sysobjid
                    , 'src_objid' => $meeting_id
                ]);
            }
            $event->src_url = route('meetings.edit', $meeting_id);
            $event->inituserid = 1; // system  - автоматом защитимся от изменений автором совещания //$meeting->inituserid;
            $event->title = mb_substr('совещание: ' . ($meeting->name ?? '-без темы-'), 0, 160);
            $event->descript = $meeting->descript;
            $event->event_place = $meeting->meet_place;
            $event->begdt = $meeting->meet_begdt;
            $event->enddt = $meeting->meet_enddt;
            $event->buildobjid = $meeting->buildobjid;
            $event->color = "#FF8C00"; //darkOrange
            $event->public_lvl = 1; //для неуастников видно только дата и время
            $event->active = 1;

            $event->save();

            //отберем участноков совещания-пользователей ИС
            $meeting_staff = meeting_staff::from('meeting_staffs as s')
                ->join('orgstaff as os', 'os.id', 's.staffid')
                ->where('protid', $meeting->id)
                ->whereNotNull('os.userid')
                ->select('os.userid')
                ->get();
            //каждого добавим в участники события
            foreach ($meeting_staff as $user) {
                obj_reader::addOrUpdate(951, $event->id, $user->userid, 1);
            }

            $tags = 'совещание';
            objtag::attach(951, $event->id, $tags);

            $rslt->err = 0;
            $rslt->msg = 'Создано связанное событие в календаре участников совещания!';
        } else
            $rslt->err = 1;
        $rslt->msg = 'Указанное совещание не найдено!';

        return $rslt;
    }

    static public function disable_event($meeting_id)
    {
        //деактивируем связанное событие (events)

        $rslt = new Result;

        // попробуем найти и сразу блокировать связанное событие:
        $cnt = event::where(['src_sysobjid' => self::$sysobjid, 'src_objid' => $meeting_id])
            ->update(['active' => 0]);
        if ($cnt == 0) {
            $rslt->err = 1;
            $rslt->msg = '';
        } else {
            $rslt->err = 0;
            $rslt->msg = '';
        }
        return $rslt;
    }

    static public function remove_event($meeting_id)
    {
        //удалим связанное событие (events)

        $rslt = new Result;

        // попробуем найти связанное событие:
        $event = event::where(['src_sysobjid' => self::$sysobjid
            , 'src_objid' => $meeting_id])->first();

        if (isset($event)) {

            //удалим связанные записи из obj_readers
            obj_reader::where([
                'sysobjid' => 951
                , 'objid' => $event->id])
                ->delete();

            $event->delete();

            $rslt->err = 0;
            $rslt->msg = 'Удалено связанное событие в календаре участников совещания!';
        } else {
            $rslt->err = 1;
            $rslt->msg = 'Указанное совещание не найдено!';
        }

        return $rslt;
    }
}
