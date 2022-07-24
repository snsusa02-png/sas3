<?php

namespace App;

use App\Jobs\SendNotify;
use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class task extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'tasks';
    static public $sysobjid = 961;


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

    public function executor()
    {
        return $this->hasOne(User::class, 'id', 'exeuserid')->withDefault();
    }

    public function srcsysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'srcsysobjыйдid')->withDefault();
    }


    public static function public_lvls()
    {
        return [0 => 'личная запись', 1 => 'видны дата/время', 2 => 'публичная'];
    }

    public static function exe_statuses()
    {
        //return [1 => 'в ожидании', 2 => 'выполняется', 3 => 'выполнено', 4 => 'не выполнено', 5 => 'отменено'];
        return [2 => 'выполняется', 3 => 'выполнено', 4 => 'не выполнено'];
    }

    public static function user_roles($p_userid)
    {
        $sc = " exists( select 1 from task_users as tu where tu.roletypeid=rt.id and tu.userid={$p_userid})";
        $lst = roletype::from('roletypes as rt')
            ->whereRaw($sc)
            ->select('rt.id', 'rt.name')
            ->orderBy('rt.name', 'asc')
            ->get()->pluck('name', 'id')->toArray();
        return $lst;
    }

    public static function task_users($p_userid)
    {
        if (usrsysright::isUserHasRightByCode_cached($p_userid, 'tasks.read'))
            $lst = user::lstFor([
                'in_tasks' => 1,
            ]);
        else
            $lst = user::lstFor([
                'in_tasks_for_user' => $p_userid,
            ]);
        return $lst;
    }

    public function tags()
    {
        return $this->hasMany(objtag::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('tag');
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('mustread', 'desc')
            ->orderby('firstread_at');
    }

    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = 'Задача: "' . $this->inituser->name . ': ' . $this->name . '"';
            if (isset($this->event_place))
                $rslt .= ', место: ' . $this->event_place;
            return $rslt;
        } else
            return null;
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

    public static function cache_clear($rec)
    {
        //Забудем связанный кэш -------------------------------------
        if (isset($rec)) {
        }
        Cache::forget('informer_all_saldos');   //из-за того что в информере отбражаются задачи, связанные с контрагентом

        Cache::forget('informer_user_active_tasks*');
        foreach (task_user::from('task_users as tu')->join('tasks as t', 't.id', 'tu.taskid')->whereNull('t.statusid')->select('tu.userid')->distinct()->get() as $itm) {
            Cache::forget('informer_user_active_tasks' . $itm->userid);
        }

        //-----------------------------------------------------------
    }

    public static function on_update($rec)
    {
        // Доп. действия при изменении записи


        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    public static function on_delete($rec)
    {
        // Доп. действия при удалении записи -------------------------


        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

        // -----------------------------------------------------------
    }


    public static function make_notifies()
    {
        //формирование уведомлений по текущему состоянию всей БД на сегодня

        //отберем записи для которых наступило время отправки уведомления, но ранее уведомление еще не отправлялось
        $recs = task::from('tasks as e')
            ->whereNotNull('e.notify_before')
            ->wherenull('e.notified_at')
            ->whereRaw("time_to_sec( TIMEDIFF(e.begdt, now())) <= time_to_sec(notify_before)")
            ->select('e.*')
            ->get();
        //dd($recs);

        if (count($recs) > 0) {
            $enddt = new DateTime('tomorrow');
            foreach ($recs as $rec) {

                //"играем" на том, что автор как читатель появится при первом же открытии своего же события
                $rcpts = User::from('users as u')
                    ->whereraw("exists (select 1 from obj_readers as r
                    where r.sysobjid=" . self::$sysobjid
                        . " and r.objid=" . $rec->id
                        . " and r.userid=u.id)")
                    ->get();

                foreach ($rcpts as $rcpt) {

                    //Добавим уведомление с временем жизни до начала события ($rec->enddt)
                    $subj = 'Уведомление: "' . $rec->title . '" - ' . date_create($rec->begdt)->format('d.m.Y H:i');

                    user_notice::addOrUpdate(951, route("tasks.edit", $rec->id), $rcpt->id
                        , $subj
                        , $rec->descript
                        , now(), max($enddt, date_create($rec->enddt)));

                    if (isset($rcpt->email)) {
                        $email = $rcpt->email;

                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

                            $msg = "Здравствуйте, " . $rcpt->fname . " " . $rcpt->mname . "!"
                                . "<br>"
                                . "<br>Напоминаем Вам о задаче:"
                                . "<br><b>" . $rec->title . "</b>"
                                . "<br> " . $rec->descript
                                . "<br>Начало: <b>" . date_create($rec->begdt)->format('d.m.Y H:i') . "</b>"
                                . "<br>Место: <b>" . $rec->event_place . "</b>"
                                . "<br><hr>"
                                . " <a href='" . route('tasks.edit', $rec->id) . "'>Перейти к записи</a>";
                            //dd($subj, $msg);
                            dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        }
                    }
                }

                //Отметим, что уведомили
                $rec->notified_at = now();
                $rec->save();
            }
        } else {
            user_notice::removeByEventID(951);
        }

        //удалить ззавершенные (по времени отображения) уведомления
        user_notice::removeEnded();
    }


    static public function informer_user_wait_tasks($userid = null)
    {
        //Cache::forget('informer_user_wait_tasks' . ($userid ?? '*'));
        return Cache::remember('informer_user_wait_tasks' . ($userid ?? '*'), now()->addMinutes(15)
            , function () use ($userid) {

                $lst = self::from('tasks as tsk')
                    ->join('users as iu', 'iu.id', 'tsk.inituserid')
                    ->whereIn('tsk.statusid', [1])
                    ->whereRaw("exists(select 1 from task_users as tu where tu.taskid=tsk.id and tu.roletypeid=7 and tu.userid={$userid})")
                    ->select('tsk.id', 'tsk.name', 'tsk.priority', 'tsk.plnbegdt', 'tsk.plnenddt', 'tsk.inituserid', 'iu.name as inituser_name')
                    ->orderBy('tsk.priority', 'desc')
                    ->orderBy('tsk.plnbegdt', 'asc')
                    ->get();
                return ($lst);

            });
    }

    static public function informer_user_exec_tasks($userid = null)
    {
        //Cache::forget('informer_user_exec_tasks' . ($userid ?? '*'));
        return Cache::remember('informer_user_exec_tasks' . ($userid ?? '*'), now()->addMinutes(15)
            , function () use ($userid) {

                $lst = self::from('tasks as tsk')
                    ->join('users as iu', 'iu.id', 'tsk.inituserid')
                    ->whereIn('tsk.statusid', [2])
                    ->where('tsk.exeuserid', $userid)
                    ->select('tsk.id', 'tsk.name', 'tsk.priority', 'tsk.fctbegdt', 'tsk.plnbegdt', 'tsk.plnenddt', 'tsk.progress', 'tsk.inituserid', 'iu.name as inituser_name')
                    ->orderBy('tsk.fctbegdt', 'asc')
                    ->get();
                return ($lst);

            });
    }

    static public function informer_user_active_tasks($userid = null)
    {
        //2022-03-19 SNS модификация под САС ДВ - считается, что задачи со STATUSID is Null - пользователь уже выполняет
        //Cache::forget('informer_user_active_tasks' . ($userid ?? '*'));
        return Cache::remember('informer_user_active_tasks' . ($userid ?? '*'), now()->addMinutes(12)
            , function () use ($userid) {

                $lst = self::from('tasks as tsk')
                    ->join('users as iu', 'iu.id', 'tsk.inituserid')
                    ->whereNull('tsk.statusid')
                    ->whereRaw("exists(select 1 from task_users as tu where tu.taskid=tsk.id and tu.roletypeid=7 and tu.userid={$userid})")
                    ->select('tsk.id', 'tsk.name', 'tsk.priority', 'tsk.plnbegdt', 'tsk.plnenddt', 'tsk.inituserid'
                        , 'tsk.srcobjinfo', 'iu.name as inituser_name')
                    ->orderBy('tsk.priority', 'desc')
                    ->orderBy('tsk.plnbegdt', 'asc')
                    ->get();
                return ($lst);

            });
    }

    static public function informer_users_tasks()
    {
        //2022-07-24 SNS кол-во невыполненных задач в разрезе пользователей
        //Cache::forget('informer_users_tasks');
        return Cache::remember('informer_users_tasks', now()->addMinutes(12)
            , function () {

                $lst = self::from('tasks as tsk')
                    ->join('task_users as tu', 'tu.taskid', 'tsk.id')
                    ->join('users as u', 'u.id', 'tu.userid')
                    ->whereRaw("ifnull(tsk.statusid,0)<>3")
                    ->select('tu.userid', db::raw("concat(u.lname,' ',u.fname) as name"), db::raw("count(1) as cnt"))
                    ->groupBy('tu.userid')
                    ->orderBy('cnt', 'desc')
                    ->get();
                return ($lst);

            });
    }

}
