<?php

namespace App;

use App\Jobs\SendNotify;
use Illuminate\Database\Eloquent\Model;

class task_user extends Model
{
    use \App\Traits\DeleteTrait;

    static public $prefix = 'task_users';
    static public $sysobjid = 963;

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function task()
    {
        return $this->hasOne(task::class, 'id', 'taskid');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'userid')->withDefault();
    }

    public static function roletypes()
    {
        //return [7 => 'исполнитель', 8 => 'куратор'];
        return roletype::from('roletypes as rt')
            ->join('sysobj_roletypes as so', 'so.roletypeid', 'rt.id')
            ->where('so.sysobjid', self::$sysobjid)
            ->orderby('so.ordr')
            ->orderby('rt.name')
            ->select('rt.id', 'rt.name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function pln_executors($taskid)
    {
        //return [7 => 'исполнитель', 8 => 'куратор'];
        return self::where(['taskid' => $taskid, 'roletypeid' => 7])->select('userid')->get()->pluck('userid')->toArray();
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    static public function addOrUpdate($search_params, $set_params)
    {
        if (isset($search_params) and isset($set_params)) {

            $rec = self::where($search_params)->first();

            if (!isset($rec)) {
                $rec = new self($search_params);
            }
            $rec->fill($set_params);
            $rec->save();

            self::notify($rec);

            return $rec;
        }
        return null;
    }

    public static function notify($rec)
    {
        if (1 == 1) {

            $taskid = $rec->taskid;
            $info_name = $rec->task->name;

            $subj = 'Новая задача';
            $msg = $info_name;
            $ref_url = route("tasks.edit", $taskid);

            //добавление колокольчика
            user_notice::addOrUpdate(961 * 1000000 + $taskid
                , $ref_url, $rec->userid
                , $subj
                , $msg
                , now()
                , null);

            //
            $rcpt = User::find($rec->userid);
            if (isset($rcpt) and isset($rcpt->email)) {

                $email = $rcpt->email;
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

                    //для журнала сформируем список получателей
                    $lstrcpts = ' ' . $rcpt->lname
                        . ' ' . mb_substr($rcpt->fname, 0, 1) . '.'
                        . mb_substr($rcpt->mname, 0, 1) . '. (' . $email . ');';

                    //$email = 'shevchenko.s@basko.su';
                    //$email = 'snsusa02@gmail.com';

                    $msg = "Здравствуйте, " . $rcpt->fname . " " . $rcpt->mname . "!"
                        . "<br>"
                        . "<br>Вам необходимо ознакомиться с новой задачей"
                        . "<br><hr>"
                        . " <a href='" . $ref_url . "'>Перейти к задаче</a>";
                    //dd($subj, $msg);
                    dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                }
            }


        }

    }

}
