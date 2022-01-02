<?php

namespace App\Http\Controllers;

use App\org;
use Illuminate\Http\Request;
use App\refitem;

class SortController extends Controller
{
    public function __construct()
    {
        //$this->middleware('auth');
    }

    public function index_sort(Request $request, $field, $retroute)
    {
        //

        //пересоберем массив сортируемых колонок
        //порядок смены: null -> 'asc' -> 'desc' -> null
        $params = [];
        //var_dump($params);

        $exist = false;
        $params_name = 'sort_params_' . $retroute;
        if ($request->session()->exists($params_name)) {
            $arr = session($params_name);
            foreach ($arr as $prm) {
                //var_dump($prm['field'], $prm['field'] == $field);
                if ($prm['field'] == $field) {
                    $exist = true;
                    if ($prm['dir'] == 'asc') {
                        //добавляем элемент с новым значением сортировки
                        $params[] = ['field' => $field, 'dir' => 'desc'];
                    }
                    //иначе значение сортировки == -1 и его нужно сменить на null, то есть удалить (не добавлять)
                } else {
                    //скопируем без изменений
                    $params[] = $prm;
                }
                //var_dump($params);
            }
        }
        if (!$exist) {
            $params[] = ['field' => $field, 'dir' => 'asc'];
        }
        //session(['sort_params' => $params]);
        //$request->session()->put('sort_params' , $params);
        session()->put($params_name, $params);
        //var_dump(session('sort_params'));
        //$request->session->put('sort_params' , $params);
        //dd(1111,$field, $request->session('sort_params'), $request->session()->all());
        //dd(1111);
        return redirect()->route($retroute);
    }

}
