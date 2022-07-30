<?php

namespace App\Http\Controllers;

//use App\buildobj;
//use App\event;
use App\obj_link;
use App\objtag;
use App\task;
use App\task_user;
use App\User;
use App\user_assistant;
use Illuminate\Http\Request;
use Redirect, Response;
use DB;

class TaskCalendarController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 961;
        $this->sysobjcode = 'tasks';
    }

    public function index(Request $request)
    {

        if (request()->ajax()) {
            $start = (!empty($_GET["start"])) ? ($_GET["start"]) : ('');
            $end = (!empty($_GET["end"])) ? ($_GET["end"]) : ('');

            $data = task::whereDate('begdt', '>=', $start)
                ->whereDate('enddt', '<=', $end)
                ->get(['id', 'name as title'
                    , 'begdt as start', 'enddt as end'
                    , "'#fff' as color"]);
            //dd(Response::json($data));
            return Response::json($data);
        }

        $s_userid = $request->get("s_userid");
        //dd($s_userid);

        if (\Auth::user()->active == 0)
            return view('home');


        $userid = \Auth::user()->id;

        $data = new \stdClass();
        $data->userid = \Auth::user()->id;
        $data->username = \Auth::user()->short_fio;
        $data->buildobjs = []; //buildobj::lstActive();
        $data->assisted_users = user_assistant::lstOwnUsers($userid);
        $data->public_lvls = [0 => 'личное', 1 => 'показывать только дату/время', 2 => 'публичное'];

        $data->s_users = User::from('users as u')
            ->where('u.active', 1)
            ->whereRaw("(
            exists( select 1 from tasks as e where e.inituserid=u.id)
            or exists( select 1 from obj_readers as r where r.sysobjid=951 and r.userid=u.id)
            )")
            ->select('u.id', 'u.name')
            ->orderby('u.name')
            ->get()
            ->pluck('name', 'id')->toArray();

        $data->search_params['s_userid'] = $s_userid;
        $data->aux_params = '';
        if (isset($s_userid)) {
            $data->aux_params = "su=1&uid=$s_userid";
        }


        //сформируем список ID представляемых пользователей для js-массива для регулирования прав доступа --------------
        $lst = '';
        foreach ($data->assisted_users as $key => $name) {
            $lst .= $key . ',';
        }
        if (isset($lst)) {
            $lst = rtrim($lst, ',');
        }
        //dd($lst);
        $data->users_id = $lst;
        //--------------------------------------------------------------------------------------------------------------
        //dd($data);

        return view('tasks.calendar', compact('data'));
    }

    public function get()
    {
        $userid = \Auth::user()->id;
        $sysobjid = $this->sysobjid;
        $assisted_users = user_assistant::lstOwnUsers($userid);
        $assisted_userids = [];
        foreach ($assisted_users as $key => $val) {
            $assisted_userids[] = $key;
        }

        $start = (!empty($_GET["start"])) ? ($_GET["start"]) : ('');
        $end = (!empty($_GET["end"])) ? ($_GET["end"]) : ('');

        $inituserid = (!empty($_GET["uid"])) ? ($_GET["uid"]) : (\Auth::user()->id);
        $strict_user = (1 == (!empty($_GET["su"])) ? ($_GET["su"]) : (''));
        //dd($userid);

        $isAssistant = false;
        //если смотрим чужой календарь
        if ($inituserid <> $userid) {

            //проверим - являемся ли Мы помощником выбранного пользователя
            $isAssistant = user_assistant::isAssistantFor($inituserid, $userid, 961);
        } elseif ($inituserid == $userid)
            $isAssistant = true;    //Сам себе помощник

        //dd($isAssistant, $strict_user );

        //Отбираем, если
        // - собственное событие
        // - есть в списке участников
        // событие публичное(2), или хотя бы обозначает дату/время(1)
        $sc = "(1=0";

        //свои события
        $sc .= " or e.inituserid=$inituserid";

        // события где добавлен как участник
        $sc .= " or exists(select 1 from obj_readers as r where r.sysobjid=951 and r.objid=e.id
            and r.userid=$inituserid)";

        if (!$strict_user)
            $sc .= " or e.public_lvl in (1,2) ";
        $sc .= " )";

        //для "чужих" событий смотрим только публичные: 1,2
        if (!$isAssistant)
            $sc .= ' and e.public_lvl in (1,2)';
        //dd($sc);


        $data = task::from('tasks as e')
            ->leftjoin('users as iu', 'iu.id', 'e.inituserid')
            ->leftjoin('users as ru', 'ru.id', 'e.updated_by')
            ->where('e.active', 1)
            ->whereDate('e.plnbegdt', '>=', $start)
            ->whereDate('e.plnenddt', '<=', $end)
            ->whereraw($sc)
            ->get([
                'e.id', 'e.name as title'
                , 'e.plnbegdt as start', 'e.plnenddt as end'
                //, db::raw("'#cfc' as color")
                , 'e.color'
                , 'e.inituserid'
                , 'e.descript'
                , db::raw(" ' ' as event_place")
                , db::raw(" ' ' as src_url")
                , 'e.public_lvl'
                , db::raw("'' as buildobjid")
                , db::raw("'' as notify_before")
                , 'inituserid', 'iu.name as initusername'
                , 'ru.name as regusername'
                , db::raw("(SELECT GROUP_CONCAT(t.tag SEPARATOR ', ')
                    FROM objtags AS t where t.sysobjid=$sysobjid and t.objid=e.id) as tags")
            ]);

        //Зачистим данные если событие не этого пользователя
        foreach ($data as $itm) {
            $may_edit = in_array($itm->inituserid, $assisted_userids);
            if ($may_edit) {
                $itm->ed = 1;
            } elseif (!$may_edit and $itm->public_lvl == 1) {
                //для чужих глаз отобразим только дату и время события
                $itm->id = 0;
                $itm->ed = 0;
                $itm->title = 'н/д: ' . $itm->initusername;
                $itm->descript = '';
                $itm->event_place = '';
                $itm->buildobjid = '';
                $itm->tags = '';
            }
        }

        //dd(Response::json($data));
        return Response::json($data);
    }

    public function create(Request $request)
    {
        $userid = \Auth::user()->id;

        $insertArr = [
            'name' => $request->title,
            'inituserid' => $request->userid ?? $userid,
            'exeuserid' => $request->exeuserid ?? $request->userid,
            'plnbegdt' => $request->start,
            'plnenddt' => $request->end,
            //'event_place' => $request->event_place,
            'color' => $request->color,
            'descript' => $request->descript,
            'public_lvl' => $request->public_lvl ?? 0,
            //'buildobjid' => $request->buildobjid,
            //'notify_before' => $request->notify_before,
            'created_at' => now(),
            'created_by' => $userid,
            'updated_at' => now(),
            'updated_by' => $userid
        ];

        $event = task::create($insertArr);
        //dd($request->tags, $event);

        objtag::attach($this->sysobjid, $event->id, $request->tags);

        // Привязка к объекту строительства -------------------------------------------------
//        $buildobjid = $request->get('buildobjid');
//        obj_link::addOrUpdateSingle($this->sysobjid, $event->id, 466, $buildobjid);
        //-----------------------------------------------------------------------------------

        // если не указан исполнитель, то сделаем им инициатора
        $exeuserid = $request->exeuserid ?? $request->userid;
        task_user::addOrUpdate(
            ['taskid' => $event->id,
                'userid' => $exeuserid,
                'roletypeid' => 7   //исполнитель
            ]
            , [
            'active' => 1,
            'updated_by' => $userid,
            'updated_at' => now(),
        ]);
        //-----------------------------------------------------------------------------------

        return Response::json($event);
    }

    public function update(Request $request)
    {
        $id = $request->id;

        if (isset($id)) {
            $userid = \Auth::user()->id;
            $sysobjid = $this->sysobjid;

            $where = array('id' => $id);
            $updateArr = ['name' => $request->title
                , 'plnbegdt' => $request->start
                , 'plnenddt' => ($request->end ?? $request->start)
                //, 'event_place' => $request->event_place
                , 'color' => $request->color
                , 'inituserid' => $request->userid ?? $userid
                , 'exeuserid' => $request->exeuserid ?? $userid
                , 'descript' => $request->descript
                , 'public_lvl' => $request->public_lvl
                //, 'buildobjid' => $request->buildobjid
                //, 'notify_before' => $request->notify_before
                //, 'notified_at' => null
                , 'updated_at' => now()
                , 'updated_by' => $userid

            ];
            $tags = $request->tags;
            $cnt = task::from('tasks as e')
                ->where($where)
                //на изменение записей пользователя имеют право его помощники
                ->whereRaw("(inituserid=$userid  or exists(select 1 from user_assistants as ua where ua.userid=e.inituserid
                and ua.active
                and ifnull(for_sysobjid,$sysobjid)=$sysobjid
                and now() between ua.begdt and ifnull(ua.enddt,now())
                and ua.assistant_userid=$userid
                ))")
                ->update($updateArr);

            objtag::attach($this->sysobjid, $id, $tags);

            // Привязка к объекту строительства -------------------------------------------------
//            $buildobjid = $request->get('buildobjid');
//            obj_link::addOrUpdateSingle($this->sysobjid, $id, 466, $buildobjid);
            //-----------------------------------------------------------------------------------

            // если не указан исполнитель, то сделаем им инициатора
            $exeuserid = $request->exeuserid ?? $request->userid;
            task_user::addOrUpdate(
                ['taskid' => $id,
                    'userid' => $exeuserid,
                    'roletypeid' => 7   //исполнитель
                ]
                , [
                'active' => 1,
                'updated_by' => $userid,
                'updated_at' => now(),
            ]);
            //-----------------------------------------------------------------------------------


            $event = task::find($id);

            return Response::json($event);
        }
        return Response::json('');
    }

    public function move(Request $request)
    {
        $userid = \Auth::user()->id;
        $where = array('id' => $request->id);
        $updateArr = [
            'plnbegdt' => $request->start
            , 'plnenddt' => ($request->end ?? $request->start)
            //, 'notified_at' => null
        ];
        //dd($where, $updateArr);
        $sysobjid = $this->sysobjid;
        $event = task::from('tasks as e')
            ->where($where)
            //->where('inituserid', $userid)
            //на изменение записей пользователя имеют право его помощники
            ->whereRaw("(inituserid=$userid  or exists(select 1 from user_assistants as ua where ua.userid=e.inituserid
                and ua.active
                and ifnull(for_sysobjid,$sysobjid)=$sysobjid
                and now() between ua.begdt and ifnull(ua.enddt,now())
                and ua.assistant_userid=$userid
                ))")
            ->update($updateArr);
        return Response::json($event);
    }

    public function destroy(Request $request)
    {
        $userid = \Auth::user()->id;
        $sysobjid = $this->sysobjid;

        $event = task::from('tasks as e')
            ->where('id', $request->id)
            //->where('inituserid', $userid)
            //на изменение записей пользователя имеют право его помощники
            ->whereRaw("(inituserid=$userid or exists(select 1 from user_assistants as ua where ua.userid=e.inituserid
                and ua.active
                and ifnull(for_sysobjid,$sysobjid)=$sysobjid
                and now() between ua.begdt and ifnull(ua.enddt,now())
                and ua.assistant_userid=$userid ))")
            ->delete();
        //dd($event);
        return Response::json($event);
    }
}
