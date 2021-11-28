<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\Excludable;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class user_template extends Model
{
    use DeleteTrait;
    use FilesTrait;
    use Excludable;

    protected $guarded = [];

    static public $prefix = 'user_templates';
    static public $sysobjid = 887;

    //protected $hidden = ['id', 'created_by', 'updated_by'];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'sysobjid');
    }

    public static function addOrUpdate($userid, $sysobjid, $template)
    {
        //dd($userid, $sysobjid, $template);
        //Создает или обновляет существующую запись о шаблоне
        if (isset($userid) and isset($sysobjid)) {

            $rec = self::where(['userid' => $userid, 'sysobjid' => $sysobjid])->first();
            if (!isset($rec)) {
                $rec = new self([
                    'userid' => $userid,
                    'sysobjid' => $sysobjid,
                ]);
            }
            $rec->template = $template;
            $rec->save();

            return $rec;
        }
    }

    public static function getTemplate($userid, $sysobjid)
    {
        //dd($userid, $sysobjid, $template);
        //Возвращает данные шаблона
        if (isset($userid) and isset($sysobjid)) {

            $rec = self::where(['userid' => $userid, 'sysobjid' => $sysobjid])->first();
            if (isset($rec)) {
                return json_decode($rec->template);
            }

            return null;
        }
    }


    public static function removeTemplate($userid, $sysobjid)
    {
        //Удаляет заданный шаблон
        if (isset($userid) and isset($sysobjid)) {

            $tmp = self::where(['userid' => $userid, 'sysobjid' => $sysobjid])->delete();
            return null;
        }
    }


    static public function search_cond($params)
    {

        $sc = "1=1";

        //пользователь ДОЛЖЕН иметь доступ к категории информации, для того, чтобы работать с ней
        $userid = \Auth::user()->id;
//        if (!usrsysright::isUserHasRightByCode_cached($userid, 'acs.admin'))
//            $sc .= " and exists (select 1 from user_acs as uac where uac.acsid=ac.id and uac.userid={$userid})";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'active') {
                        $sc .= " and d.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (d.active=1 or d.id={$val})";

                    } elseif ($key == 'new_for_user_or_current') {
                        $t_userid = $val[0] ?? 0;
                        $t_acsid = $val[1] ?? 0;
                        $sc .= " and d.active=1
                            and not exists (select 1 from user_acs as uac where uac.acsid=ac.id
                            and uac.userid={$t_userid} and uac.acsid<>{$t_acsid})";
                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;
    }

    static public function lstFor($params)
    {
        //2021-02-18 SNS. универсальный конструктор массива с id, name документов
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('documents as d')
                ->whereRaw($sc)
                ->select('d.id', 'd.name')
                ->orderBy('d.docdate', 'asc')
                ->orderBy('d.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            //asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

}
