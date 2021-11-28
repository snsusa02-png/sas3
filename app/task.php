<?php

namespace App;

use App\Jobs\SendNotify;
use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
            $rslt = 'Событие: "' . $this->title . '", ' . date_format(date_create($this->begdt), "d.m.Y H:i");
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

}
