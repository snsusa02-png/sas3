<?php

namespace App\Traits;

//use Illuminate\Support\Facades\Request;
use Illuminate\Http\Request;

trait SearchDataTrait
{
    public $pageitmcnts = ["10" => "10", '20' => '20'
        , '50' => '50'
        , '100' => '100'
        , '99999' => 'все'];

    public function search_params(Request $request, $param_names, $env_code = null)
    {
        // Общая часть обработки переменных поиска --------------------------------------------------------------------

        $env_code = $env_code ?? $this->sysobjcode; //уникальный код места применения этих действий. Обычно - код системы
                                                    // но иногда в одной системе есть несколько разных мест, тогда - что-то уникальное

        //инициализация переменных для отбора записей
        foreach ($param_names as $item => $val) {
            $$item = '';
        }


        //if ($request::isMethod('post')) {
        if ($request->isMethod('post')) {

            //считаем значения из формы и заодно сформируем массив для сохранения в сессии
            $tses = [];
            foreach ($param_names as $item => $val) {
                //$$item = $request::get($item);
                $$item = $request->get($item);
                $tses[$item] = $$item;
            }
            //сохраним параметры поиска в сессии
            //session(['search_setname' => $this->sysobjcode]);
            session(['search_setname' => $env_code]);
            session(['search_params' => $tses]);

        } else {

            $tses = [];

            //if (session('search_setname') == $this->sysobjcode) {
            if (session('search_setname') == $env_code) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $tses = session('search_params');
                    foreach ($param_names as $item => $val) {
                        $$item = $tses[$item] ?? null;
                    }
                }

            } else {

                //зачистим чужие параметры поиска
                session(['search_params' => []]);
                $tses = [];

                //используем значения по умолчанию -----------------------------
                foreach ($param_names as $item => $val) {
                    $$item = $val;
                }
                //--------------------------------------------------------------
            }

            //2021-08-17 SNS. Возможно критерий поиска был передан через ссылку (GET). Проверим:
            foreach ($param_names as $item => $val) {
                $tval = ($request->get($item));
                if (isset($tval)) {
                    $$item = $tval;
                    $tses[$item] = $$item;
                }
            }
            if (1 == 1) {
                //сохраним параметры поиска в сессии
                //session(['search_setname' => $this->sysobjcode]);
                session(['search_setname' => $env_code]);
                session(['search_params' => $tses]);
            }

        }

        //сформируем массив для передачи в форму
        $search_params = [];
        foreach ($param_names as $item => $val) {
            $search_params[$item] = $$item;
        }

//        $needSearch = false;
//        foreach ($search_params as $p) {
//            if (isset($p)) {
//                $needSearch = true;
//                break;
//            }
//        }
        //-------------------------------------------------------------------------------------------------------------

        return $search_params;
    }

}
